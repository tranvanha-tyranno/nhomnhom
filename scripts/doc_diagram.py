from PIL import Image, ImageDraw, ImageFont


def draw_relationships(target):
    image = Image.new("RGB", (1600, 1080), "white")
    draw = ImageDraw.Draw(image)
    font = ImageFont.truetype("C:/Windows/Fonts/arial.ttf", 25)
    small = ImageFont.truetype("C:/Windows/Fonts/arial.ttf", 22)
    bold = ImageFont.truetype("C:/Windows/Fonts/arialbd.ttf", 28)
    boxes = {
        "users": (40, 430, 340, 550), "restaurants": (640, 430, 960, 550),
        "dishes": (1260, 430, 1560, 550), "favorites": (340, 190, 640, 290),
        "reviews": (340, 710, 640, 810), "menu_items": (980, 190, 1280, 290),
        "areas": (640, 10, 960, 110), "dish_ingredients": (1180, 710, 1540, 810),
        "ingredients": (1180, 950, 1540, 1050),
    }
    lines = [
        ((190, 430), (340, 290), "1", "N"), ((800, 430), (640, 290), "1", "N"),
        ((190, 550), (340, 710), "1", "N"), ((800, 550), (640, 710), "1", "N"),
        ((960, 430), (980, 290), "1", "N"), ((1410, 430), (1280, 290), "1", "N"),
        ((800, 110), (800, 430), "1", "N"), ((1410, 550), (1360, 710), "1", "N"),
        ((1360, 950), (1360, 810), "1", "N"),
    ]
    for start, end, left, right in lines:
        draw.line([start, end], fill="#71869B", width=4)
        for pos, fraction in [(left, .15), (right, .82)]:
            x = start[0] + (end[0] - start[0]) * fraction
            y = start[1] + (end[1] - start[1]) * fraction
            draw.rectangle((x-2, y-4, x+22, y+25), fill="white")
            draw.text((x, y), pos, font=small, fill="black")
    for name, (x1, y1, x2, y2) in boxes.items():
        draw.rounded_rectangle((x1, y1, x2, y2), radius=10, fill="#EDF3F7", outline="#71869B", width=2)
        draw.text(((x1+x2)/2, (y1+y2)/2), name, anchor="mm", font=bold if name in ["users", "restaurants", "dishes"] else font, fill="black")
    image.save(target)
