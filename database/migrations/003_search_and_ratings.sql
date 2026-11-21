CREATE TRIGGER reviews_insert AFTER INSERT ON reviews WHEN NEW.status = 'published' BEGIN
  UPDATE restaurants SET rating_sum = rating_sum + NEW.rating, review_count = review_count + 1
  WHERE id = NEW.restaurant_id;
END;
CREATE TRIGGER reviews_delete AFTER DELETE ON reviews WHEN OLD.status = 'published' BEGIN
  UPDATE restaurants SET rating_sum = rating_sum - OLD.rating, review_count = review_count - 1
  WHERE id = OLD.restaurant_id;
END;
CREATE TRIGGER reviews_update AFTER UPDATE OF rating, status, restaurant_id ON reviews BEGIN
  UPDATE restaurants SET rating_sum = rating_sum - OLD.rating, review_count = review_count - 1
  WHERE id = OLD.restaurant_id AND OLD.status = 'published';
  UPDATE restaurants SET rating_sum = rating_sum + NEW.rating, review_count = review_count + 1
  WHERE id = NEW.restaurant_id AND NEW.status = 'published';
END;
CREATE VIRTUAL TABLE restaurant_search USING fts5(name, content, tokenize = 'unicode61 remove_diacritics 2');
CREATE VIEW catalog_search_documents AS
SELECT r.id, r.name,
  r.name || ' ' || r.description || ' ' || r.address || ' ' || a.name || ' ' || c.name || ' ' ||
  COALESCE((SELECT group_concat(cu.name, ' ') FROM restaurant_cuisines rc
    JOIN cuisines cu ON cu.id = rc.cuisine_id WHERE rc.restaurant_id = r.id), '') || ' ' ||
  COALESCE((SELECT group_concat(d.name, ' ') FROM menu_items mi
    JOIN dishes d ON d.id = mi.dish_id WHERE mi.restaurant_id = r.id AND mi.is_available = 1), '') || ' ' ||
  COALESCE((SELECT group_concat(t.name, ' ') FROM restaurant_tags rt
    JOIN tags t ON t.id = rt.tag_id WHERE rt.restaurant_id = r.id), '') AS content
FROM restaurants r JOIN areas a ON a.id = r.area_id JOIN cities c ON c.id = a.city_id;
CREATE VIEW restaurant_rating_summary AS
SELECT id AS restaurant_id, review_count,
  CASE WHEN review_count = 0 THEN 0.0 ELSE round(1.0 * rating_sum / review_count, 1) END AS average_rating
FROM restaurants;
