CREATE TABLE countries (
  id INTEGER PRIMARY KEY, code TEXT NOT NULL UNIQUE CHECK(length(code) = 2), name TEXT NOT NULL
) STRICT;
CREATE TABLE cities (
  id INTEGER PRIMARY KEY, country_id INTEGER NOT NULL REFERENCES countries(id),
  name TEXT NOT NULL, latitude REAL NOT NULL CHECK(latitude BETWEEN -90 AND 90),
  longitude REAL NOT NULL CHECK(longitude BETWEEN -180 AND 180), UNIQUE(country_id, name)
) STRICT;
CREATE TABLE areas (
  id INTEGER PRIMARY KEY, city_id INTEGER NOT NULL REFERENCES cities(id),
  name TEXT NOT NULL, UNIQUE(city_id, name)
) STRICT;
CREATE TABLE cuisines (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE, description TEXT NOT NULL
) STRICT;
CREATE TABLE restaurants (
  id INTEGER PRIMARY KEY, area_id INTEGER NOT NULL REFERENCES areas(id),
  name TEXT NOT NULL CHECK(length(name) BETWEEN 1 AND 160), slug TEXT NOT NULL UNIQUE,
  description TEXT NOT NULL, address TEXT NOT NULL,
  latitude REAL NOT NULL CHECK(latitude BETWEEN -90 AND 90),
  longitude REAL NOT NULL CHECK(longitude BETWEEN -180 AND 180),
  price_level INTEGER NOT NULL CHECK(price_level BETWEEN 1 AND 4),
  status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','closed','pending')),
  is_demo INTEGER NOT NULL DEFAULT 1 CHECK(is_demo IN (0,1)),
  rating_sum INTEGER NOT NULL DEFAULT 0 CHECK(rating_sum >= 0),
  review_count INTEGER NOT NULL DEFAULT 0 CHECK(review_count >= 0),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK(rating_sum BETWEEN review_count AND review_count * 5)
) STRICT;
CREATE TABLE restaurant_cuisines (
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  cuisine_id INTEGER NOT NULL REFERENCES cuisines(id), PRIMARY KEY(restaurant_id, cuisine_id)
) STRICT;
CREATE TABLE opening_hours (
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  weekday INTEGER NOT NULL CHECK(weekday BETWEEN 0 AND 6),
  opens_minute INTEGER NOT NULL CHECK(opens_minute BETWEEN 0 AND 1439),
  closes_minute INTEGER NOT NULL CHECK(closes_minute BETWEEN 1 AND 1440),
  PRIMARY KEY(restaurant_id, weekday), CHECK(opens_minute < closes_minute)
) STRICT;
CREATE TABLE amenities (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE
) STRICT;
CREATE TABLE restaurant_amenities (
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  amenity_id INTEGER NOT NULL REFERENCES amenities(id), PRIMARY KEY(restaurant_id, amenity_id)
) STRICT;
CREATE TABLE categories (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE
) STRICT;
CREATE TABLE dishes (
  id INTEGER PRIMARY KEY, category_id INTEGER NOT NULL REFERENCES categories(id),
  cuisine_id INTEGER NOT NULL REFERENCES cuisines(id), name TEXT NOT NULL,
  description TEXT NOT NULL, is_vegetarian INTEGER NOT NULL CHECK(is_vegetarian IN (0,1)),
  spice_level INTEGER NOT NULL CHECK(spice_level BETWEEN 0 AND 3), UNIQUE(name, cuisine_id)
) STRICT;
CREATE TABLE menu_items (
  id INTEGER PRIMARY KEY, restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  dish_id INTEGER NOT NULL REFERENCES dishes(id),
  price_vnd INTEGER NOT NULL CHECK(price_vnd BETWEEN 0 AND 100000000),
  is_available INTEGER NOT NULL DEFAULT 1 CHECK(is_available IN (0,1)),
  UNIQUE(restaurant_id, dish_id)
) STRICT;
CREATE TABLE ingredients (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE
) STRICT;
CREATE TABLE dish_ingredients (
  dish_id INTEGER NOT NULL REFERENCES dishes(id) ON DELETE CASCADE,
  ingredient_id INTEGER NOT NULL REFERENCES ingredients(id),
  PRIMARY KEY(dish_id, ingredient_id)
) STRICT;
CREATE TABLE allergens (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE
) STRICT;
CREATE TABLE dish_allergens (
  dish_id INTEGER NOT NULL REFERENCES dishes(id) ON DELETE CASCADE,
  allergen_id INTEGER NOT NULL REFERENCES allergens(id), PRIMARY KEY(dish_id, allergen_id)
) STRICT;
CREATE TABLE tags (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE
) STRICT;
CREATE TABLE restaurant_tags (
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  tag_id INTEGER NOT NULL REFERENCES tags(id), PRIMARY KEY(restaurant_id, tag_id)
) STRICT;
CREATE TABLE restaurant_photos (
  id INTEGER PRIMARY KEY, restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  url TEXT NOT NULL CHECK(url LIKE '/images/%' OR url LIKE 'https://%'),
  caption TEXT NOT NULL, is_cover INTEGER NOT NULL DEFAULT 0 CHECK(is_cover IN (0,1))
) STRICT;
CREATE UNIQUE INDEX idx_one_cover ON restaurant_photos(restaurant_id) WHERE is_cover = 1;
CREATE INDEX idx_restaurants_area ON restaurants(area_id, status);
CREATE INDEX idx_restaurants_status ON restaurants(status, id);
CREATE INDEX idx_menu_dish ON menu_items(dish_id, restaurant_id);
CREATE INDEX idx_cuisine_restaurants ON restaurant_cuisines(cuisine_id, restaurant_id);
CREATE INDEX idx_amenity_restaurants ON restaurant_amenities(amenity_id, restaurant_id);
CREATE INDEX idx_tag_restaurants ON restaurant_tags(tag_id, restaurant_id);
CREATE INDEX idx_dishes_category ON dishes(category_id);
CREATE INDEX idx_dishes_cuisine ON dishes(cuisine_id);
CREATE INDEX idx_ingredient_dishes ON dish_ingredients(ingredient_id, dish_id);
CREATE INDEX idx_allergen_dishes ON dish_allergens(allergen_id, dish_id);
CREATE INDEX idx_photos_restaurant ON restaurant_photos(restaurant_id);
