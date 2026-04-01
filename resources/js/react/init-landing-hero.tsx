import { createRoot, type Root } from "react-dom/client";

import {
  HeroGeometric,
  type HeroAction,
  type HeroFeature,
  type HeroRole,
  type HeroStat,
} from "@/components/ui/shape-landing-hero";

type LandingHeroPayload = {
  badge?: string;
  title?: string;
  description?: string;
  primaryCta?: HeroAction | null;
  secondaryCta?: HeroAction | null;
  stats?: HeroStat[];
  roles?: HeroRole[];
  features?: HeroFeature[];
  visualImage?: string;
};

const heroRoots = new WeakMap<HTMLElement, Root>();

function splitHeadline(title: string): [string, string] {
  const normalizedTitle = title.trim();
  if (!normalizedTitle) {
    return ["Elevate Your Hiring Engine", "With Faster AI Screening"];
  }

  const words = normalizedTitle.split(/\s+/);
  if (words.length <= 4) {
    return [normalizedTitle, ""];
  }

  let splitIndex = Math.ceil(words.length / 2);
  let bestDistance = Number.POSITIVE_INFINITY;

  for (let index = 2; index <= words.length - 2; index += 1) {
    const firstSegmentLength = words.slice(0, index).join(" ").length;
    const distance = Math.abs(firstSegmentLength - 18);

    if (distance < bestDistance) {
      bestDistance = distance;
      splitIndex = index;
    }
  }

  return [words.slice(0, splitIndex).join(" "), words.slice(splitIndex).join(" ")];
}

function parsePayload(rawPayload: string | undefined): LandingHeroPayload | null {
  if (!rawPayload) {
    return null;
  }

  try {
    return JSON.parse(rawPayload) as LandingHeroPayload;
  } catch (error) {
    console.error("Could not parse landing hero payload.", error);
    return null;
  }
}

export function initLandingHeroes(): void {
  const mounts = Array.from(
    document.querySelectorAll<HTMLElement>("[data-landing-hero-root]"),
  );

  mounts.forEach((mount) => {
    const payload = parsePayload(mount.dataset.landingHeroProps);
    if (!payload) {
      return;
    }

    const [title1, title2] = splitHeadline(payload.title ?? "");
    const root = heroRoots.get(mount) ?? createRoot(mount);

    heroRoots.set(mount, root);
    root.render(
      <HeroGeometric
        badge={payload.badge}
        title1={title1}
        title2={title2}
        description={payload.description}
        primaryCta={payload.primaryCta ?? undefined}
        secondaryCta={payload.secondaryCta ?? undefined}
        stats={payload.stats}
        roles={payload.roles}
        featureCards={payload.features}
        visualImage={payload.visualImage}
      />,
    );
  });
}
