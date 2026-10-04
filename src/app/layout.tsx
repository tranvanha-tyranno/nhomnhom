import type { Metadata } from "next";

import "./globals.css";

export const metadata: Metadata = {
  title: "NhomNhom",
  description: "Tìm món ăn yêu thích và mở chỉ đường Google Maps."
};

type RootLayoutProps = Readonly<{
  children: React.ReactNode;
}>;

export default function RootLayout({ children }: RootLayoutProps) {
  return (
    <html lang="vi">
      <body>{children}</body>
    </html>
  );
}
