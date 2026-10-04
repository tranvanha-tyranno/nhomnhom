import { Search } from "lucide-react";

type SearchBoxProps = {
  query: string;
  onQueryChange: (query: string) => void;
};

export function SearchBox({ query, onQueryChange }: SearchBoxProps) {
  return (
    <label className="searchBox">
      <Search aria-hidden="true" size={20} />
      <span className="srOnly">Tìm món ăn</span>
      <input
        maxLength={80}
        onChange={(event) => onQueryChange(event.target.value)}
        placeholder="Bạn muốn ăn gì hôm nay?"
        type="search"
        value={query}
      />
    </label>
  );
}
