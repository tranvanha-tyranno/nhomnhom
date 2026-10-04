export type TravelMode = "driving" | "walking" | "bicycling" | "transit";

export type FoodPlace = {
  id: string;
  name: string;
  cuisine: string;
  description: string;
  address: string;
  lat: number;
  lng: number;
  tags: readonly string[];
  priceLevel: 1 | 2 | 3 | 4;
  rating: number;
};

export const foodPlaces = [
  {
    id: "bun-bo-hue-co-ba",
    name: "Bún Bò Huế Cô Ba",
    cuisine: "Bún bò Huế",
    description: "Tô bún bò cay thơm, hợp cho buổi trưa cần nhiều năng lượng.",
    address: "Khu vực Quận 1, TP. Hồ Chí Minh",
    lat: 10.7758,
    lng: 106.7019,
    tags: ["bun bo", "hue", "cay", "bo"],
    priceLevel: 2,
    rating: 4.8
  },
  {
    id: "pho-ga-pho-co",
    name: "Phở Gà Phố Cổ",
    cuisine: "Phở",
    description: "Nước dùng thanh, thịt gà mềm, dễ ăn cho bữa sáng.",
    address: "Khu vực Quận 3, TP. Hồ Chí Minh",
    lat: 10.7826,
    lng: 106.6848,
    tags: ["pho", "ga", "an sang", "nuoc dung"],
    priceLevel: 2,
    rating: 4.6
  },
  {
    id: "com-chay-an-lanh",
    name: "Cơm Chay An Lành",
    cuisine: "Món chay",
    description: "Cơm phần nhiều rau, vị nhẹ, phù hợp khi muốn ăn thanh đạm.",
    address: "Khu vực Bình Thạnh, TP. Hồ Chí Minh",
    lat: 10.8011,
    lng: 106.7112,
    tags: ["chay", "com", "healthy", "rau"],
    priceLevel: 1,
    rating: 4.7
  },
  {
    id: "banh-mi-bo-kho-ngon",
    name: "Bánh Mì Bò Kho Ngon",
    cuisine: "Bánh mì",
    description: "Bò kho sánh, ăn kèm bánh mì nóng cho bữa tối nhanh gọn.",
    address: "Khu vực Phú Nhuận, TP. Hồ Chí Minh",
    lat: 10.7982,
    lng: 106.6778,
    tags: ["banh mi", "bo kho", "toi", "nong"],
    priceLevel: 2,
    rating: 4.5
  },
  {
    id: "goi-cuon-tuoi",
    name: "Gỏi Cuốn Tươi",
    cuisine: "Món cuốn",
    description: "Gỏi cuốn nhẹ bụng, nước chấm đậm vị, hợp khi đi theo nhóm.",
    address: "Khu vực Quận 4, TP. Hồ Chí Minh",
    lat: 10.7596,
    lng: 106.7062,
    tags: ["goi cuon", "cuon", "nhom", "tuoi"],
    priceLevel: 1,
    rating: 4.4
  }
] as const satisfies readonly FoodPlace[];
