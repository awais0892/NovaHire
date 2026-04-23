"use client";

import type { AnimationItem } from "lottie-web";
import lottie from "lottie-web/build/player/lottie_light";
import { memo, useEffect, useRef, useState } from "react";

type AnalysisLottieLoaderProps = {
  autoplay?: boolean;
  className?: string;
  loop?: boolean;
  size?: number;
  speed?: number;
  src: string | Record<string, unknown>;
};

function AnalysisLottieLoaderComponent({
  autoplay = true,
  className,
  loop = true,
  size = 220,
  speed = 1,
  src,
}: AnalysisLottieLoaderProps) {
  const [hasLoadError, setHasLoadError] = useState(false);
  const containerRef = useRef<HTMLDivElement | null>(null);
  const wrapperClassName = ["flex items-center justify-center", className]
    .filter(Boolean)
    .join(" ");

  useEffect(() => {
    const container = containerRef.current;
    if (!container) {
      return;
    }

    container.innerHTML = "";

    let animation: AnimationItem | null = null;
    const onDataFailed = () => {
      setHasLoadError(true);
    };

    try {
      const baseConfig = {
        container,
        renderer: "svg" as const,
        loop,
        autoplay,
        rendererSettings: {
          preserveAspectRatio: "xMidYMid meet",
          progressiveLoad: true,
        },
      };

      animation =
        typeof src === "string"
          ? lottie.loadAnimation({
              ...baseConfig,
              path: src,
            })
          : lottie.loadAnimation({
              ...baseConfig,
              animationData: src,
            });

      animation.setSpeed(speed);
      animation.addEventListener("data_failed", onDataFailed);
      animation.addEventListener("error", onDataFailed);
    } catch (error) {
      onDataFailed();
    }

    return () => {
      if (!animation) {
        return;
      }

      animation.removeEventListener("data_failed", onDataFailed);
      animation.removeEventListener("error", onDataFailed);
      animation.destroy();
      container.innerHTML = "";
    };
  }, [autoplay, loop, speed, src]);

  if (hasLoadError) {
    return (
      <div
        className={wrapperClassName}
        style={{ height: `${size}px`, width: `${size}px` }}
        role="img"
        aria-label="AI analysis in progress"
      >
        <div className="h-12 w-12 animate-spin rounded-full border-2 border-brand-300/25 border-t-brand-300" />
      </div>
    );
  }

  return (
    <div
      className={wrapperClassName}
      role="img"
      aria-label="AI analysis in progress"
    >
      <div
        ref={containerRef}
        style={{ height: `${size}px`, width: `${size}px`, display: "block" }}
      />
    </div>
  );
}

export const AnalysisLottieLoader = memo(AnalysisLottieLoaderComponent);
