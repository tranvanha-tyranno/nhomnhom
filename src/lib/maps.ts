import type { TravelMode } from "./foodPlaces";

const allowedTravelModes: readonly TravelMode[] = [
  "driving",
  "walking",
  "bicycling",
  "transit"
];

type DirectionsInput = {
  lat: number;
  lng: number;
  travelMode?: TravelMode;
};

export function buildGoogleMapsDirectionsUrl({
  lat,
  lng,
  travelMode = "driving"
}: DirectionsInput): string {
  validateCoordinate("Latitude", lat, -90, 90);
  validateCoordinate("Longitude", lng, -180, 180);

  if (!allowedTravelModes.includes(travelMode)) {
    throw new Error("Unsupported travel mode.");
  }

  const params = new URLSearchParams({
    api: "1",
    destination: `${lat},${lng}`,
    travelmode: travelMode
  });

  return `https://www.google.com/maps/dir/?${params.toString()}`;
}

function validateCoordinate(
  label: "Latitude" | "Longitude",
  value: number,
  min: number,
  max: number
): void {
  if (!Number.isFinite(value) || value < min || value > max) {
    throw new RangeError(`${label} is outside the supported range.`);
  }
}
