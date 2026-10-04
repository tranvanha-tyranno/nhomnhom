"use client";

import { useMemo, useState } from "react";
import { MapPin, Utensils } from "lucide-react";

import { foodPlaces, type TravelMode } from "@/lib/foodPlaces";
import { searchFoodPlaces } from "@/lib/foodSearch";

import { ResultCard } from "./ResultCard";
import { SearchBox } from "./SearchBox";
import { TravelModeSelector } from "./TravelModeSelector";

const quickQueries = ["bún bò", "phở", "chay", "gỏi cuốn"];

export function FoodSearchApp() {
  const [query, setQuery] = useState("");
  const [travelMode, setTravelMode] = useState<TravelMode>("driving");
  const results = useMemo(() => searchFoodPlaces(foodPlaces, query), [query]);

  return (
    <main className="appShell">
      <section className="heroBand" aria-labelledby="app-title">
        <div className="heroContent">
          <div className="brandBlock">
            <div className="brandBadge" aria-label="NhomNhom">
              <Utensils aria-hidden="true" size={20} />
              <span>NhomNhom</span>
            </div>
            <h1 id="app-title">NhomNhom</h1>
            <p>Tìm món hợp gu rồi mở Google Maps đến điểm ăn gần đúng với lựa chọn của bạn.</p>
          </div>

          <div className="searchPanel" aria-label="Tìm món ăn">
            <SearchBox query={query} onQueryChange={setQuery} />
            <div className="quickQueryRow" aria-label="Gợi ý món ăn">
              {quickQueries.map((item) => (
                <button
                  className="quickQuery"
                  key={item}
                  onClick={() => setQuery(item)}
                  type="button"
                >
                  {item}
                </button>
              ))}
            </div>
            <TravelModeSelector travelMode={travelMode} onTravelModeChange={setTravelMode} />
          </div>
        </div>
      </section>

      <section className="resultsBand" aria-labelledby="results-title">
        <div className="resultsContent">
          <div className="resultsHeader">
            <div>
              <span className="sectionEyebrow">Danh sách gợi ý</span>
              <h2 id="results-title">Món phù hợp</h2>
            </div>
            <div className="resultCount" aria-live="polite">
              <MapPin aria-hidden="true" size={18} />
              <span>{results.length} điểm ăn</span>
            </div>
          </div>

          {results.length > 0 ? (
            <div className="resultsGrid">
              {results.map((place, index) => (
                <ResultCard
                  key={place.id}
                  place={place}
                  rank={index + 1}
                  travelMode={travelMode}
                />
              ))}
            </div>
          ) : (
            <div className="emptyState" role="status">
              <MapPin aria-hidden="true" size={22} />
              <span>Chưa có món phù hợp.</span>
            </div>
          )}
        </div>
      </section>
    </main>
  );
}
