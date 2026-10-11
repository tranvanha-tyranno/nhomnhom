CREATE TABLE users (
  id INTEGER PRIMARY KEY, email TEXT NOT NULL UNIQUE COLLATE NOCASE CHECK(email LIKE '%_@_%._%'),
  display_name TEXT NOT NULL CHECK(length(display_name) BETWEEN 1 AND 100),
  status TEXT NOT NULL DEFAULT 'demo' CHECK(status IN ('demo','active','disabled')),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
) STRICT;
CREATE TABLE user_preferences (
  user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  preferred_cuisine_id INTEGER REFERENCES cuisines(id),
  max_price_vnd INTEGER CHECK(max_price_vnd BETWEEN 0 AND 100000000),
  vegetarian_only INTEGER NOT NULL DEFAULT 0 CHECK(vegetarian_only IN (0,1)),
  travel_mode TEXT NOT NULL DEFAULT 'walking' CHECK(travel_mode IN ('walking','driving','bicycling','transit'))
) STRICT;
CREATE TABLE favorites (
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id, restaurant_id)
) STRICT;
CREATE TABLE collections (
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name TEXT NOT NULL CHECK(length(name) BETWEEN 1 AND 120),
  visibility TEXT NOT NULL DEFAULT 'private' CHECK(visibility IN ('private','public')),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, name)
) STRICT;
CREATE TABLE collection_items (
  collection_id INTEGER NOT NULL REFERENCES collections(id) ON DELETE CASCADE,
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  note TEXT NOT NULL DEFAULT '' CHECK(length(note) <= 1000), PRIMARY KEY(collection_id, restaurant_id)
) STRICT;
CREATE TABLE reviews (
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
  body TEXT NOT NULL CHECK(length(body) BETWEEN 1 AND 2000),
  status TEXT NOT NULL DEFAULT 'published' CHECK(status IN ('published','pending','hidden')),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, restaurant_id)
) STRICT;
CREATE TABLE review_votes (
  review_id INTEGER NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(review_id, user_id)
) STRICT;
CREATE TABLE search_history (
  id INTEGER PRIMARY KEY, user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  query TEXT NOT NULL CHECK(length(query) <= 80),
  result_count INTEGER NOT NULL CHECK(result_count >= 0), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
) STRICT;
CREATE TABLE direction_events (
  id INTEGER PRIMARY KEY, user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
  travel_mode TEXT NOT NULL CHECK(travel_mode IN ('walking','driving','bicycling','transit')),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
) STRICT;
CREATE TABLE audit_events (
  id INTEGER PRIMARY KEY, actor_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  action TEXT NOT NULL CHECK(length(action) BETWEEN 1 AND 100),
  entity_type TEXT NOT NULL, entity_id TEXT NOT NULL,
  metadata_json TEXT NOT NULL DEFAULT '{}' CHECK(json_valid(metadata_json)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
) STRICT;
CREATE TABLE seed_runs (
  name TEXT PRIMARY KEY, restaurant_count INTEGER NOT NULL, user_count INTEGER NOT NULL,
  completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
) STRICT;
CREATE INDEX idx_preferences_cuisine ON user_preferences(preferred_cuisine_id);
CREATE INDEX idx_favorites_restaurant ON favorites(restaurant_id, user_id);
CREATE INDEX idx_collection_restaurant ON collection_items(restaurant_id, collection_id);
CREATE INDEX idx_reviews_restaurant ON reviews(restaurant_id, status, created_at);
CREATE INDEX idx_votes_user ON review_votes(user_id);
CREATE INDEX idx_search_user_time ON search_history(user_id, created_at);
CREATE INDEX idx_directions_user_time ON direction_events(user_id, created_at);
CREATE INDEX idx_directions_restaurant ON direction_events(restaurant_id, created_at);
CREATE INDEX idx_audit_actor ON audit_events(actor_id, created_at);
CREATE INDEX idx_audit_entity ON audit_events(entity_type, entity_id, created_at);
