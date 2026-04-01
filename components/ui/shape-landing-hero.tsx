"use client";

import { animate, motion, useMotionValue, useTransform } from "framer-motion";
import {
  ArrowRight,
  BarChart3,
  BrainCircuit,
  BriefcaseBusiness,
  CheckCircle2,
  Circle,
  ClipboardCheck,
  CreditCard,
  LineChart,
  MapPin,
  ScanSearch,
  ShieldCheck,
  Sparkles,
  Users,
} from "lucide-react";
import { useEffect, useState } from "react";

import { cn } from "@/lib/utils";

export type HeroAction = {
  label: string;
  href: string;
};

export type HeroStat = {
  label: string;
  value: string;
};

export type HeroFeature = {
  icon?: string;
  title: string;
  desc?: string;
};

export type HeroRole = {
  title: string;
  points: string[];
};

type HeroCandidate = {
  image: string;
  location: string;
  name: string;
  role: string;
  score: number;
};

type HeroGeometricProps = {
  badge?: string;
  title1?: string;
  title2?: string;
  description?: string;
  primaryCta?: HeroAction;
  secondaryCta?: HeroAction;
  stats?: HeroStat[];
  featureCards?: HeroFeature[];
  roles?: HeroRole[];
  visualImage?: string;
};

const defaultPrimaryCta: HeroAction = {
  label: "Start Free",
  href: "/register",
};

const defaultSecondaryCta: HeroAction = {
  label: "Browse Jobs",
  href: "/jobs",
};

const defaultStats: HeroStat[] = [
  { label: "Candidates Screened", value: "1.2M+" },
  { label: "Avg. Time-to-Hire Reduction", value: "67%" },
  { label: "Enterprise Customers", value: "850+" },
];

const defaultFeatures: HeroFeature[] = [
  {
    icon: "brain-circuit",
    title: "AI Resume Intelligence",
    desc: "Score fit, extract skills, and keep recruiter reviews consistent.",
  },
  {
    icon: "clipboard-check",
    title: "Workflow Automation",
    desc: "Route every applicant from shortlist to interview without manual churn.",
  },
  {
    icon: "shield-check",
    title: "Bias-Aware Reviews",
    desc: "Keep hiring decisions tied to structured, role-specific criteria.",
  },
];

const defaultRoles: HeroRole[] = [
  {
    title: "HR Admin",
    points: ["Team setup", "Company controls", "Global hiring metrics"],
  },
  {
    title: "Recruiter",
    points: ["AI screening", "Candidate ranking", "Pipeline actions"],
  },
  {
    title: "Hiring Manager",
    points: ["Shortlists", "Interview review", "Decision support"],
  },
];

const defaultCandidates: HeroCandidate[] = [
  {
    name: "Ava Patel",
    role: "Senior Product Recruiter",
    location: "London",
    score: 96,
    image:
      "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80",
  },
  {
    name: "Marcus Reed",
    role: "Technical Sourcer",
    location: "Manchester",
    score: 92,
    image:
      "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80",
  },
  {
    name: "Sofia Chen",
    role: "People Ops Specialist",
    location: "Bristol",
    score: 89,
    image:
      "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=300&q=80",
  },
];

const featureIconMap = {
  "bar-chart-3": BarChart3,
  "brain-circuit": BrainCircuit,
  "clipboard-check": ClipboardCheck,
  "credit-card": CreditCard,
  "shield-check": ShieldCheck,
  users: Users,
} as const;

const dramaticEase = [0.16, 1, 0.3, 1] as const;
const revealEase = [0.22, 1, 0.36, 1] as const;

function parseMetricValue(value: string) {
  const match = value.match(/^([^0-9-]*)(-?\d+(?:\.\d+)?)(.*)$/);
  if (!match) {
    return null;
  }

  const [, prefix, rawNumber, suffix] = match;
  return {
    decimals: rawNumber.includes(".") ? rawNumber.split(".")[1]?.length ?? 0 : 0,
    prefix,
    suffix,
    target: Number(rawNumber),
  };
}

function formatMetricValue(
  prefix: string,
  suffix: string,
  decimals: number,
  currentValue: number,
) {
  return `${prefix}${currentValue.toFixed(decimals)}${suffix}`;
}

function ElegantShape({
  className,
  delay = 0,
  width = 400,
  height = 100,
  rotate = 0,
  gradient = "from-white/[0.08]",
}: {
  className?: string;
  delay?: number;
  width?: number;
  height?: number;
  rotate?: number;
  gradient?: string;
}) {
  return (
    <motion.div
      initial={{
        opacity: 0,
        rotate: rotate - 10,
        y: -72,
      }}
      animate={{
        opacity: 1,
        rotate,
        y: 0,
      }}
      transition={{
        delay,
        duration: 0.9,
        ease: dramaticEase,
        opacity: { duration: 0.45 },
      }}
      className={cn("absolute", className)}
    >
      <motion.div
        animate={{
          y: [0, 6, 0],
        }}
        transition={{
          duration: 7.2,
          ease: "easeInOut",
          repeat: Number.POSITIVE_INFINITY,
        }}
        style={{
          height,
          width,
        }}
        className="relative"
      >
        <div
          className={cn(
            "absolute inset-0 rounded-full",
            "bg-gradient-to-r to-transparent",
            gradient,
            "border-2 border-white/[0.15] backdrop-blur-[2px]",
            "shadow-[0_8px_32px_0_rgba(255,255,255,0.1)]",
            "after:absolute after:inset-0 after:rounded-full",
            "after:bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.2),transparent_70%)]",
          )}
        />
      </motion.div>
    </motion.div>
  );
}

function AnimatedStatValue({
  className,
  value,
}: {
  className?: string;
  value: string;
}) {
  const parsedValue = parseMetricValue(value);
  const prefix = parsedValue?.prefix ?? "";
  const suffix = parsedValue?.suffix ?? "";
  const decimals = parsedValue?.decimals ?? 0;
  const target = parsedValue?.target ?? 0;
  const isNumericValue = parsedValue !== null;

  const motionValue = useMotionValue(0);
  const transformedValue = useTransform(motionValue, (latest) =>
    formatMetricValue(prefix, suffix, decimals, latest),
  );
  const [displayValue, setDisplayValue] = useState(() =>
    isNumericValue
      ? formatMetricValue(prefix, suffix, decimals, 0)
      : value,
  );

  useEffect(() => {
    if (!isNumericValue) {
      setDisplayValue(value);
      return;
    }

    motionValue.set(0);
    const unsubscribe = transformedValue.on("change", (latest) => {
      setDisplayValue(latest);
    });
    const controls = animate(motionValue, target, {
      delay: 0.08,
      duration: 1.05,
      ease: dramaticEase,
    });

    return () => {
      unsubscribe();
      controls.stop();
    };
  }, [isNumericValue, motionValue, target, transformedValue, value]);

  return <span className={className}>{displayValue}</span>;
}

function resolveFeatureIcon(iconName?: string) {
  const normalizedIcon = iconName?.trim().toLowerCase() ?? "";
  return featureIconMap[
    normalizedIcon as keyof typeof featureIconMap
  ] ?? Sparkles;
}

function TalentRow({
  candidate,
  delay,
}: {
  candidate: HeroCandidate;
  delay: number;
}) {
  return (
    <motion.div
      initial={{ opacity: 0, x: 24 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{
        delay,
        duration: 0.48,
        ease: revealEase,
      }}
      className="rounded-[22px] border border-white/[0.08] bg-white/[0.04] p-3.5 shadow-[0_14px_32px_rgba(2,6,23,0.14)] backdrop-blur-xl"
    >
      <div className="flex items-start gap-3">
        <img
          src={candidate.image}
          alt={candidate.name}
          className="size-11 rounded-[18px] object-cover"
          loading="lazy"
        />

        <div className="min-w-0 flex-1">
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="truncate text-sm font-semibold text-white">
                {candidate.name}
              </p>
              <p className="mt-0.5 text-xs text-white/55">
                {candidate.role}
              </p>
            </div>
            <span className="shrink-0 rounded-full border border-emerald-300/15 bg-emerald-400/15 px-2.5 py-1 text-[11px] font-semibold text-emerald-100">
              {candidate.score}% fit
            </span>
          </div>

          <div className="mt-2 flex items-center gap-1.5 text-[11px] text-white/45">
            <MapPin className="size-3.5" />
            <span>{candidate.location}</span>
          </div>

          <div className="mt-2 h-1.5 rounded-full bg-white/10">
            <motion.div
              initial={{ width: 0 }}
              animate={{ width: `${candidate.score}%` }}
              transition={{
                delay: delay + 0.08,
                duration: 0.52,
                ease: dramaticEase,
              }}
              className="h-full rounded-full bg-gradient-to-r from-indigo-300 via-cyan-300 to-emerald-300"
            />
          </div>
        </div>
      </div>
    </motion.div>
  );
}

function HeroGeometric({
  badge = "Design Collective",
  title1 = "Elevate Your Digital Vision",
  title2 = "Crafting Exceptional Websites",
  description = "Crafting exceptional digital experiences through innovative design and cutting-edge technology.",
  primaryCta = defaultPrimaryCta,
  secondaryCta = defaultSecondaryCta,
  stats = defaultStats,
  featureCards = defaultFeatures,
  roles = defaultRoles,
  visualImage = "https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1400&q=80",
}: HeroGeometricProps) {
  const fadeUpVariants = {
    hidden: { opacity: 0, y: 14 },
    visible: (index: number) => ({
      opacity: 1,
      transition: {
        delay: 0.03 + index * 0.07,
        duration: 0.52,
        ease: dramaticEase,
      },
      y: 0,
    }),
  };

  const featuredStats = stats.length ? stats.slice(0, 3) : defaultStats;
  const spotlightFeatures = featureCards.length
    ? featureCards.slice(0, 3)
    : defaultFeatures;
  const teamRoles = roles.length ? roles.slice(0, 3) : defaultRoles;
  const stageMetrics = [
    {
      label: "Hours saved / recruiter",
      note: "Every active search",
      value: "48h",
    },
    {
      label: "Live role coverage",
      note: "Shared across teams",
      value: "14",
    },
    {
      label: "Auto-ranked today",
      note: "Qualified applicants",
      value: "312",
    },
  ];
  const stageCardClassName =
    "rounded-[22px] border border-white/[0.08] bg-slate-950/48 p-4 shadow-[0_16px_36px_rgba(2,6,23,0.22)] backdrop-blur-2xl";
  const stageMetaCardClassName =
    "overflow-hidden rounded-[16px] border border-white/[0.08] bg-black/24 px-3 py-2.5";

  return (
    <section className="relative py-12 sm:py-14 lg:py-16">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="relative isolate overflow-hidden rounded-[2rem] border border-slate-200/70 bg-[#06080f] shadow-sm dark:border-slate-800/80">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_14%_14%,rgba(99,102,241,0.2),transparent_34%),radial-gradient(circle_at_82%_12%,rgba(244,63,94,0.12),transparent_24%),linear-gradient(180deg,#090f1f_0%,#06080f_56%,#05070e_100%)]" />
          <div className="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.04)_1px,transparent_1px)] bg-[size:44px_44px] opacity-20 [mask-image:radial-gradient(circle_at_center,black,transparent_85%)]" />

          <div className="absolute inset-0 overflow-hidden">
        <ElegantShape
          delay={0.26}
          width={420}
          height={96}
          rotate={10}
          gradient="from-indigo-500/[0.16]"
          className="left-[-16%] top-[12%] md:left-[-8%]"
        />
        <ElegantShape
          delay={0.36}
          width={360}
          height={80}
          rotate={-10}
          gradient="from-violet-500/[0.14]"
          className="bottom-[8%] left-[0%] md:left-[8%]"
        />
        <ElegantShape
          delay={0.42}
          width={240}
          height={54}
          rotate={18}
          gradient="from-amber-500/[0.14]"
          className="right-[14%] top-[10%] hidden sm:block"
        />
        <ElegantShape
          delay={0.5}
          width={300}
          height={72}
          rotate={-16}
          gradient="from-rose-500/[0.14]"
          className="right-[-8%] top-[72%] hidden lg:block"
        />
      </div>

      <div className="relative z-10 grid gap-7 p-5 sm:p-7 lg:grid-cols-[1fr_1.02fr] lg:items-center lg:gap-8 lg:p-9 xl:p-10">
        <div className="max-w-[38rem]">
          <motion.div
            custom={0}
            variants={fadeUpVariants}
            initial="hidden"
            animate="visible"
            className="inline-flex items-center gap-2 rounded-full border border-white/[0.12] bg-white/[0.04] px-3 py-1.5"
          >
            <Circle className="size-2 fill-rose-400/85 text-rose-400/85" />
            <span className="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/68">
              {badge}
            </span>
          </motion.div>

          <motion.div
            custom={1}
            variants={fadeUpVariants}
            initial="hidden"
            animate="visible"
          >
            <h1 className="mt-5 max-w-[15ch] text-balance text-[clamp(2.6rem,5vw,4.9rem)] font-semibold leading-[0.9] tracking-[-0.06em] text-white">
              <span className="block bg-gradient-to-b from-white via-white to-white/75 bg-clip-text text-transparent">
                {title1}
              </span>
              {title2 ? (
                <span className="mt-0.5 block bg-gradient-to-r from-indigo-200 via-white to-rose-200 bg-clip-text text-transparent">
                  {title2}
                </span>
              ) : null}
            </h1>
          </motion.div>

          <motion.p
            custom={2}
            variants={fadeUpVariants}
            initial="hidden"
            animate="visible"
            className="mt-5 max-w-[32rem] text-sm leading-relaxed text-white/62 sm:text-base"
          >
            {description}
          </motion.p>

          <motion.div
            custom={3}
            variants={fadeUpVariants}
            initial="hidden"
            animate="visible"
            className="mt-7 flex flex-wrap gap-3"
          >
            <a
              href={primaryCta.href}
              className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5 hover:bg-slate-100"
            >
              {primaryCta.label}
              <ArrowRight className="size-4" />
            </a>

            {secondaryCta ? (
              <a
                href={secondaryCta.href}
                className="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/[0.06] px-5 py-2.5 text-sm font-semibold text-white/88 backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white/[0.11]"
              >
                <BriefcaseBusiness className="size-4" />
                {secondaryCta.label}
              </a>
            ) : null}
          </motion.div>

          <motion.div
            custom={4}
            variants={fadeUpVariants}
            initial="hidden"
            animate="visible"
            className="mt-8 grid gap-2.5 sm:grid-cols-3"
          >
            {featuredStats.map((stat, index) => (
              <motion.div
                key={stat.label}
                initial={{ opacity: 0, y: 14 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{
                  delay: 0.2 + index * 0.05,
                  duration: 0.4,
                  ease: dramaticEase,
                }}
                className="rounded-2xl border border-white/[0.1] bg-white/[0.05] px-4 py-3.5 backdrop-blur-xl"
              >
                <AnimatedStatValue
                  value={stat.value}
                  className="text-2xl font-semibold tracking-tight text-white sm:text-[1.75rem]"
                />
                <p className="mt-1.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/40">
                  {stat.label}
                </p>
              </motion.div>
            ))}
          </motion.div>
        </div>

        <div className="relative w-full">
          <motion.div
            initial={{ opacity: 0, x: 20, y: 12, scale: 0.985 }}
            animate={{ opacity: 1, x: 0, y: 0, scale: 1 }}
            transition={{
              delay: 0.12,
              duration: 0.62,
              ease: dramaticEase,
            }}
            className="relative w-full"
          >
            <div className="pointer-events-none absolute inset-x-10 top-6 h-24 rounded-full bg-indigo-500/14 blur-3xl" />
            <div className="overflow-hidden rounded-[28px] border border-white/10 bg-slate-950/65 p-3.5 shadow-[0_28px_80px_rgba(2,6,23,0.4)]">
              <div className="relative overflow-hidden rounded-[22px] border border-white/10 bg-slate-950">
                <div className="absolute inset-0">
                  <img
                    src={visualImage}
                    alt="Recruiting team collaboration"
                    className="h-full w-full object-cover opacity-[0.15]"
                  />
                  <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.24)_0%,rgba(2,6,23,0.72)_44%,rgba(2,6,23,0.95)_100%)]" />
                </div>

                <div className="relative space-y-3 p-3.5 sm:p-4">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1.5">
                      <p className="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/62">
                        Live Candidate Intelligence
                      </p>
                    </div>

                    <div className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1.5 text-[11px] font-semibold text-white/72">
                      <span className="inline-flex size-2 rounded-full bg-emerald-300 shadow-[0_0_0_5px_rgba(110,231,183,0.14)]" />
                      Realtime scoring active
                    </div>
                  </div>

                  <motion.div
                    initial={{ opacity: 0, y: 14 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                      delay: 0.24,
                      duration: 0.45,
                      ease: dramaticEase,
                    }}
                    className={stageCardClassName}
                  >
                    <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                      <div className={cn(stageMetaCardClassName, "relative pr-12 sm:col-span-2 xl:col-span-1")}>
                        <p className="text-[10px] font-semibold uppercase tracking-[0.17em] text-white/38">
                          Pipeline Pulse
                        </p>
                        <AnimatedStatValue
                          value={featuredStats[1]?.value ?? "67%"}
                          className="mt-2 block text-[2rem] font-semibold leading-none tracking-[-0.04em] text-white"
                        />
                        <p className="mt-1 text-xs text-white/52">Avg. time-to-hire reduction</p>
                        <div className="absolute right-3 top-3 rounded-xl border border-emerald-300/15 bg-emerald-400/15 p-2 text-emerald-100">
                          <LineChart className="size-4" />
                        </div>
                      </div>
                      {stageMetrics.map((metric, index) => (
                        <motion.div
                          key={metric.label}
                          initial={{ opacity: 0, y: 10 }}
                          animate={{ opacity: 1, y: 0 }}
                          transition={{
                            delay: 0.29 + index * 0.04,
                            duration: 0.36,
                            ease: dramaticEase,
                          }}
                          className={stageMetaCardClassName}
                        >
                          <p className="text-[10px] font-semibold uppercase tracking-[0.17em] text-white/36">
                            {metric.label}
                          </p>
                          <AnimatedStatValue
                            value={metric.value}
                            className="mt-2 block text-[1.55rem] font-semibold leading-none tracking-[-0.04em] text-white"
                          />
                          <p className="mt-1 text-[11px] text-white/44">
                            {metric.note}
                          </p>
                        </motion.div>
                      ))}
                    </div>
                  </motion.div>

                  <div className="grid gap-3 xl:grid-cols-[1.06fr_0.94fr]">
                    <motion.div
                      initial={{ opacity: 0, y: 14 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{
                        delay: 0.32,
                        duration: 0.44,
                        ease: dramaticEase,
                      }}
                      className={stageCardClassName}
                    >
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/38">
                            AI Shortlist
                          </p>
                          <p className="mt-1 text-xs text-white/56">
                            Ranked candidates ready for recruiter review
                          </p>
                        </div>
                        <div className="rounded-xl border border-cyan-300/15 bg-cyan-400/12 p-2.5 text-cyan-100">
                          <ScanSearch className="size-4.5" />
                        </div>
                      </div>

                      <div className="mt-3 space-y-2.5">
                        {defaultCandidates.map((candidate, index) => (
                          <TalentRow
                            key={candidate.name}
                            candidate={candidate}
                            delay={0.38 + index * 0.05}
                          />
                        ))}
                      </div>
                    </motion.div>

                    <div className="grid gap-3">
                      <motion.div
                        initial={{ opacity: 0, y: 14 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                          delay: 0.38,
                          duration: 0.44,
                          ease: dramaticEase,
                        }}
                        className={stageCardClassName}
                      >
                        <div className="flex items-center justify-between gap-3">
                          <div>
                            <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/38">
                              Hiring Lanes
                            </p>
                            <p className="mt-1 text-xs text-white/56">
                              Shared visibility across every role in the process
                            </p>
                          </div>
                          <div className="rounded-xl border border-white/10 bg-white/[0.06] p-2.5 text-white">
                            <Users className="size-4.5" />
                          </div>
                        </div>

                        <div className="mt-3 space-y-2">
                          {teamRoles.map((role) => (
                            <div
                              key={role.title}
                              className="rounded-2xl border border-white/[0.08] bg-white/[0.04] px-3.5 py-2.5"
                            >
                              <div className="flex items-center justify-between gap-2">
                                <p className="text-sm font-semibold text-white">
                                  {role.title}
                                </p>
                                <span className="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/38">
                                  {role.points.length} checkpoints
                                </span>
                              </div>
                              <p className="mt-0.5 text-xs text-white/52">
                                {role.points[0] ?? "Structured collaboration"}
                              </p>
                            </div>
                          ))}
                        </div>
                      </motion.div>

                      <motion.div
                        initial={{ opacity: 0, y: 14 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                          delay: 0.44,
                          duration: 0.44,
                          ease: dramaticEase,
                        }}
                        className={stageCardClassName}
                      >
                        <div className="flex items-center justify-between gap-3">
                          <div>
                            <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/38">
                              Automation Stack
                            </p>
                            <p className="mt-1 text-xs text-white/56">
                              Core capabilities applied to every requisition
                            </p>
                          </div>
                          <div className="rounded-xl border border-rose-300/15 bg-rose-400/12 p-2.5 text-rose-100">
                            <ShieldCheck className="size-4.5" />
                          </div>
                        </div>

                        <div className="mt-3 space-y-2.5">
                          {spotlightFeatures.map((feature) => {
                            const FeatureIcon = resolveFeatureIcon(feature.icon);

                            return (
                              <div
                                key={feature.title}
                                className="rounded-2xl border border-white/[0.08] bg-white/[0.04] px-3.5 py-2.5"
                              >
                                <div className="flex items-start gap-2.5">
                                  <div className="rounded-xl border border-white/10 bg-white/[0.07] p-2 text-white">
                                    <FeatureIcon className="size-3.5" />
                                  </div>
                                  <div>
                                    <p className="text-sm font-semibold text-white">
                                      {feature.title}
                                    </p>
                                    <p className="mt-0.5 text-xs leading-5 text-white/52">
                                      {feature.desc}
                                    </p>
                                  </div>
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      </motion.div>
                    </div>
                  </div>

                  <motion.div
                    initial={{ opacity: 0, y: 14 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                      delay: 0.5,
                      duration: 0.44,
                      ease: dramaticEase,
                    }}
                    className={cn(stageCardClassName, "bg-white/[0.06]")}
                  >
                    <div className="grid gap-3 sm:grid-cols-[0.95fr_1.05fr] sm:items-center">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/38">
                            Recruiter Confidence
                          </p>
                          <p className="mt-2 text-2xl font-semibold leading-[1.1] tracking-[-0.04em] text-white">
                            Faster decisions with structured signals
                          </p>
                        </div>
                        <div className="rounded-xl border border-amber-300/15 bg-amber-400/12 p-2.5 text-amber-100">
                          <CheckCircle2 className="size-4.5" />
                        </div>
                      </div>

                      <div className="grid gap-2 sm:grid-cols-2">
                        <div className="flex items-center justify-between rounded-[14px] border border-white/[0.08] bg-black/20 px-3 py-2 text-xs">
                          <span className="text-white/62">Bias-aware scorecards</span>
                          <span className="font-semibold text-white">Enabled</span>
                        </div>
                        <div className="flex items-center justify-between rounded-[14px] border border-white/[0.08] bg-black/20 px-3 py-2 text-xs">
                          <span className="text-white/62">Interview handoff packet</span>
                          <span className="font-semibold text-white">Auto-ready</span>
                        </div>
                      </div>
                    </div>
                  </motion.div>
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      </div>
          <div className="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#05070e] to-transparent" />
        </div>
      </div>
    </section>
  );
}

export { HeroGeometric };
