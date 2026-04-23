import { spawn, spawnSync } from 'node:child_process';
import { existsSync, readdirSync } from 'node:fs';
import { stat } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import ffmpegStatic from 'ffmpeg-static';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const repoRoot = path.resolve(__dirname, '..');
const rolesDir = path.resolve(repoRoot, 'public', 'animations', 'roles');

const roleBasenames = [
    'recruiter-role',
    'manager-role',
    'hiring-manager-role',
    'candidate-role',
];

function isUsableFfmpeg(binary) {
    if (!binary) {
        return false;
    }

    const probe = spawnSync(binary, ['-version'], {
        cwd: repoRoot,
        windowsHide: true,
        stdio: 'ignore',
    });

    return probe.status === 0;
}

function resolveFfmpegBinary() {
    const explicit = process.env.FFMPEG_PATH;
    if (isUsableFfmpeg(explicit)) {
        return explicit;
    }

    const commonInstallPaths = [
        'C:\\Program Files\\FFmpeg\\bin\\ffmpeg.exe',
        'C:\\Program Files\\Gyan\\FFmpeg\\bin\\ffmpeg.exe',
    ];
    for (const candidate of commonInstallPaths) {
        if (isUsableFfmpeg(candidate)) {
            return candidate;
        }
    }

    const localAppData = process.env.LOCALAPPDATA;
    if (localAppData) {
        const wingetPackagesRoot = path.resolve(localAppData, 'Microsoft', 'WinGet', 'Packages');
        if (existsSync(wingetPackagesRoot)) {
            const packageDirectories = readdirSync(wingetPackagesRoot, { withFileTypes: true })
                .filter((entry) => entry.isDirectory() && entry.name.startsWith('Gyan.FFmpeg'))
                .map((entry) => path.resolve(wingetPackagesRoot, entry.name));

            for (const packageDirectory of packageDirectories) {
                const versionDirectories = readdirSync(packageDirectory, { withFileTypes: true })
                    .filter((entry) => entry.isDirectory() && entry.name.startsWith('ffmpeg-'))
                    .map((entry) => path.resolve(packageDirectory, entry.name, 'bin', 'ffmpeg.exe'));

                for (const candidate of versionDirectories) {
                    if (isUsableFfmpeg(candidate)) {
                        return candidate;
                    }
                }
            }
        }
    }

    if (isUsableFfmpeg('ffmpeg')) {
        return 'ffmpeg';
    }

    if (isUsableFfmpeg(ffmpegStatic)) {
        return ffmpegStatic;
    }

    throw new Error(
        'No usable ffmpeg binary found. Set FFMPEG_PATH or install ffmpeg in PATH.',
    );
}

const ffmpegBinary = resolveFfmpegBinary();

function runFfmpeg(args) {
    return new Promise((resolve, reject) => {
        const child = spawn(ffmpegBinary, args, {
            cwd: repoRoot,
            stdio: ['ignore', 'pipe', 'pipe'],
            windowsHide: true,
        });

        let stderr = '';
        child.stderr.on('data', (chunk) => {
            stderr += chunk.toString();
        });

        child.on('error', reject);
        child.on('close', (code) => {
            if (code === 0) {
                resolve();
                return;
            }

            reject(new Error(stderr.trim() || `ffmpeg exited with code ${code}`));
        });
    });
}

function bytesToKb(bytes) {
    return `${(bytes / 1024).toFixed(1)} KB`;
}

async function transcodeOne(baseName) {
    const inputPath = path.resolve(rolesDir, `${baseName}.mp4`);
    const outputPath = path.resolve(rolesDir, `${baseName}.webm`);

    await stat(inputPath);

    const args = [
        '-y',
        '-i',
        inputPath,
        '-an',
        '-vf',
        'scale=720:-2:flags=lanczos',
        '-c:v',
        'libvpx-vp9',
        '-b:v',
        '0',
        '-crf',
        '36',
        '-deadline',
        'good',
        '-cpu-used',
        '5',
        '-row-mt',
        '1',
        '-pix_fmt',
        'yuv420p',
        outputPath,
    ];

    await runFfmpeg(args);

    const inputStats = await stat(inputPath);
    const outputStats = await stat(outputPath);
    const savedBytes = inputStats.size - outputStats.size;
    const savedRatio = inputStats.size > 0
        ? (savedBytes / inputStats.size) * 100
        : 0;

    console.log(
        `${baseName}: ${bytesToKb(inputStats.size)} -> ${bytesToKb(outputStats.size)} (${savedRatio >= 0 ? '-' : '+'}${Math.abs(savedRatio).toFixed(1)}%)`,
    );
}

async function main() {
    console.log(`Using ffmpeg binary: ${ffmpegBinary}`);
    console.log(`Transcoding role animations in: ${rolesDir}`);

    for (const baseName of roleBasenames) {
        await transcodeOne(baseName);
    }

    console.log('Done. WebM variants are ready.');
}

main().catch((error) => {
    console.error('Failed to transcode role animations.');
    console.error(error?.message || error);
    process.exitCode = 1;
});
