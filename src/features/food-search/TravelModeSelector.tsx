import type { LucideIcon } from "lucide-react";
import { Bike, Car, Footprints, TrainFront } from "lucide-react";

import type { TravelMode } from "@/lib/foodPlaces";

type TravelModeOption = {
  label: string;
  mode: TravelMode;
  title: string;
  Icon: LucideIcon;
};

type TravelModeSelectorProps = {
  travelMode: TravelMode;
  onTravelModeChange: (travelMode: TravelMode) => void;
};

const travelModeOptions: readonly TravelModeOption[] = [
  { label: "Xe", mode: "driving", title: "Đi bằng xe", Icon: Car },
  { label: "Đi bộ", mode: "walking", title: "Đi bộ", Icon: Footprints },
  { label: "Xe đạp", mode: "bicycling", title: "Đi bằng xe đạp", Icon: Bike },
  { label: "Transit", mode: "transit", title: "Phương tiện công cộng", Icon: TrainFront }
];

export function TravelModeSelector({
  travelMode,
  onTravelModeChange
}: TravelModeSelectorProps) {
  return (
    <div className="modeSelector" role="group" aria-label="Chọn cách di chuyển">
      {travelModeOptions.map(({ label, mode, title, Icon }) => (
        <button
          aria-pressed={travelMode === mode}
          className="modeButton"
          key={mode}
          onClick={() => onTravelModeChange(mode)}
          title={title}
          type="button"
        >
          <Icon aria-hidden="true" size={17} />
          <span>{label}</span>
        </button>
      ))}
    </div>
  );
}
