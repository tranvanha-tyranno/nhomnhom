import type { FoodPlace } from "./foodPlaces";

type ScoredPlace = {
  place: FoodPlace;
  score: number;
};

export function normalizeSearchText(value: string): string {
  return value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase()
    .trim()
    .replace(/\s+/g, " ");
}

export function searchFoodPlaces(
  places: readonly FoodPlace[],
  rawQuery: string
): FoodPlace[] {
  if (typeof rawQuery !== "string") {
    throw new TypeError("Search query must be a string.");
  }

  const query = normalizeSearchText(rawQuery);

  return places
    .map((place) => scorePlace(place, query))
    .filter((result): result is ScoredPlace => result !== null)
    .sort(compareScoredPlaces)
    .map((result) => result.place);
}

function scorePlace(place: FoodPlace, query: string): ScoredPlace | null {
  if (!query) {
    return { place, score: place.rating };
  }

  const searchText = normalizeSearchText(
    [place.name, place.cuisine, place.description, place.address, ...place.tags].join(" ")
  );
  const tokens = query.split(" ");

  if (!tokens.every((token) => searchText.includes(token))) {
    return null;
  }

  return {
    place,
    score: place.rating + getRelevanceBoost(place, query)
  };
}

function getRelevanceBoost(place: FoodPlace, query: string): number {
  const normalizedName = normalizeSearchText(place.name);
  const normalizedCuisine = normalizeSearchText(place.cuisine);
  const normalizedTags = place.tags.map(normalizeSearchText);
  let boost = 0;

  if (normalizedName.includes(query)) {
    boost += 3;
  }

  if (normalizedCuisine.includes(query)) {
    boost += 2;
  }

  if (normalizedTags.includes(query)) {
    boost += 2;
  }

  return boost;
}

function compareScoredPlaces(left: ScoredPlace, right: ScoredPlace): number {
  return (
    right.score - left.score ||
    right.place.rating - left.place.rating ||
    left.place.name.localeCompare(right.place.name, "vi")
  );
}
