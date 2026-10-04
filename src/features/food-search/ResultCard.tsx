import { MapPin, Navigation, Star } from "lucide-react";

import type { FoodPlace, TravelMode } from "@/lib/foodPlaces";
import { buildGoogleMapsDirectionsUrl } from "@/lib/maps";

type ResultCardProps = {
  place: FoodPlace;
  rank: number;
  travelMode: TravelMode;
};

export function ResultCard({ place, rank, travelMode }: ResultCardProps) {
  const directionsUrl = buildGoogleMapsDirectionsUrl({
    lat: place.lat,
    lng: place.lng,
    travelMode
  });

  return (
    <article className="resultCard">
      <div className="resultTopline">
        <span className="rankBadge">#{rank}</span>
        <span className="ratingBadge" title={`${place.rating} sao`}>
          <Star aria-hidden="true" size={15} />
          {place.rating.toFixed(1)}
        </span>
      </div>

      <div className="resultBody">
        <p className="cuisineLabel">{place.cuisine}</p>
        <h3>{place.name}</h3>
        <p>{place.description}</p>
      </div>

      <div className="tagRow" aria-label="Thẻ món ăn">
        {place.tags.slice(0, 3).map((tag) => (
          <span key={tag}>{tag}</span>
        ))}
      </div>

      <div className="resultFooter">
        <span className="addressLine">
          <MapPin aria-hidden="true" size={16} />
          {place.address}
        </span>
        <a
          className="mapButton"
          href={directionsUrl}
          rel="noopener noreferrer"
          target="_blank"
          title={`Mở Google Maps đến ${place.name}`}
        >
          <Navigation aria-hidden="true" size={17} />
          <span>Chỉ đường</span>
        </a>
      </div>
    </article>
  );
}
