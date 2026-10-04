import { describe, expect, it } from "vitest";

import { foodPlaces } from "./foodPlaces";
import { searchFoodPlaces } from "./foodSearch";
import { buildGoogleMapsDirectionsUrl } from "./maps";

describe("searchFoodPlaces", () => {
  it("finds Vietnamese food names without requiring accents", () => {
    const results = searchFoodPlaces(foodPlaces, "bun bo hue");

    expect(results[0]?.name).toBe("Bún Bò Huế Cô Ba");
  });

  it("matches tags and cuisine words", () => {
    const results = searchFoodPlaces(foodPlaces, "chay");

    expect(results.map((place) => place.name)).toContain("Cơm Chay An Lành");
  });

  it("returns featured places by rating when the query is empty", () => {
    const results = searchFoodPlaces(foodPlaces, "  ");
    const ratings = results.map((place) => place.rating);
    const sortedRatings = [...ratings].sort((a, b) => b - a);

    expect(results).toHaveLength(foodPlaces.length);
    expect(ratings).toEqual(sortedRatings);
  });
});

describe("buildGoogleMapsDirectionsUrl", () => {
  it("builds a directions URL from coordinates and travel mode", () => {
    const url = buildGoogleMapsDirectionsUrl({
      lat: 10.7758,
      lng: 106.7019,
      travelMode: "walking"
    });

    expect(url).toBe(
      "https://www.google.com/maps/dir/?api=1&destination=10.7758%2C106.7019&travelmode=walking"
    );
  });
});
