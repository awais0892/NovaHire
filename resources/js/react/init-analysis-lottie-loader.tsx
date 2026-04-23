import { createRoot, type Root } from "react-dom/client";

import { AnalysisLottieLoader } from "@/components/ui/analysis-lottie-loader";

type LottieLoaderPayload = {
  autoplay?: boolean;
  loop?: boolean;
  size?: number;
  speed?: number;
  src?: string;
};

const DEFAULT_LOTTIE_SRC = "/animations/ai-loading-model.json";

const lottieRoots = new WeakMap<HTMLElement, Root>();

function parsePayload(rawPayload: string | undefined): LottieLoaderPayload {
  if (!rawPayload) {
    return {};
  }

  try {
    return JSON.parse(rawPayload) as LottieLoaderPayload;
  } catch (error) {
    console.error("Could not parse AI analysis Lottie payload.", error);
    return {};
  }
}

function sanitizePayload(payload: LottieLoaderPayload) {
  const size =
    typeof payload.size === "number" && Number.isFinite(payload.size)
      ? Math.max(140, Math.min(360, payload.size))
      : 220;
  const speed =
    typeof payload.speed === "number" && Number.isFinite(payload.speed)
      ? Math.max(0.2, Math.min(3, payload.speed))
      : 1;

  return {
    autoplay: payload.autoplay ?? true,
    loop: payload.loop ?? true,
    size,
    speed,
    src:
      typeof payload.src === "string" && payload.src.trim().length > 0
        ? payload.src
        : DEFAULT_LOTTIE_SRC,
  };
}

export function initAnalysisLottieLoaders(): void {
  const mounts = Array.from(
    document.querySelectorAll<HTMLElement>("[data-ai-analysis-lottie-root]"),
  );

  mounts.forEach((mount) => {
    const payload = sanitizePayload(
      parsePayload(mount.dataset.aiAnalysisLottieProps),
    );
    const root = lottieRoots.get(mount) ?? createRoot(mount);

    lottieRoots.set(mount, root);
    root.render(
      <AnalysisLottieLoader
        autoplay={payload.autoplay}
        loop={payload.loop}
        size={payload.size}
        speed={payload.speed}
        src={payload.src}
      />,
    );
  });
}
