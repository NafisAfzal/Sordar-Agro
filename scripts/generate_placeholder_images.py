from PIL import Image, ImageDraw, ImageFont
import os

W, H = 800, 600
OUT_DIR = os.path.join("storage", "app", "public", "products")
os.makedirs(OUT_DIR, exist_ok=True)

FONT_BOLD = "C:/Windows/Fonts/segoeuib.ttf"
FONT_REG = "C:/Windows/Fonts/segoeui.ttf"

CATEGORY_STYLE = {
    "fish": {"start": (25, 181, 197), "end": (7, 59, 76), "motif": "fish"},
    "aquatic-plants": {"start": (116, 198, 157), "end": (27, 94, 63), "motif": "leaf"},
    "fish-food": {"start": (255, 209, 102), "end": (224, 122, 63), "motif": "bowl"},
    "equipment": {"start": (100, 132, 145), "end": (11, 37, 48), "motif": "gear"},
}

PRODUCTS = [
    ("neon-tetra", "Neon Tetra", "fish"),
    ("betta-splendens", "Betta Splendens", "fish"),
    ("guppy", "Guppy", "fish"),
    ("goldfish", "Goldfish", "fish"),
    ("cardinal-tetra", "Cardinal Tetra", "fish"),
    ("java-fern", "Java Fern", "aquatic-plants"),
    ("amazon-sword", "Amazon Sword", "aquatic-plants"),
    ("premium-flake-food-100g", "Premium Flake Food", "fish-food"),
    ("sinking-pellets-200g", "Sinking Pellets", "fish-food"),
    ("aquarium-filter-1200lh", "Aquarium Filter", "equipment"),
    ("led-aquarium-light-60cm", "LED Aquarium Light", "equipment"),
    ("submersible-heater-100w", "Submersible Heater", "equipment"),
]


def lerp(a, b, t):
    return tuple(int(a[i] + (b[i] - a[i]) * t) for i in range(3))


def gradient_bg(start, end):
    img = Image.new("RGB", (W, H), start)
    px = img.load()
    for y in range(H):
        for x in range(W):
            t = ((x / W) + (y / H)) / 2
            px[x, y] = lerp(start, end, t)
    return img


def draw_fish(draw, cx, cy, scale, color):
    body = [(cx - 90 * scale, cy), (cx - 40 * scale, cy - 55 * scale),
            (cx + 70 * scale, cy - 30 * scale), (cx + 110 * scale, cy),
            (cx + 70 * scale, cy + 30 * scale), (cx - 40 * scale, cy + 55 * scale)]
    draw.polygon(body, fill=color)
    tail = [(cx - 90 * scale, cy), (cx - 150 * scale, cy - 45 * scale), (cx - 150 * scale, cy + 45 * scale)]
    draw.polygon(tail, fill=color)
    draw.ellipse([cx + 65 * scale, cy - 12 * scale, cx + 85 * scale, cy + 8 * scale], fill=(255, 255, 255, 230))
    draw.ellipse([cx + 71 * scale, cy - 6 * scale, cx + 81 * scale, cy + 4 * scale], fill=(20, 20, 20))


def draw_leaf(draw, cx, cy, scale, color):
    draw.ellipse([cx - 70 * scale, cy - 140 * scale, cx + 70 * scale, cy + 140 * scale], fill=color)
    draw.line([cx, cy - 130 * scale, cx, cy + 130 * scale], fill=(255, 255, 255, 180), width=int(4 * scale))
    for i in range(-3, 4):
        yoff = i * 30 * scale
        draw.line([cx, cy + yoff, cx + 45 * scale, cy + yoff - 12 * scale], fill=(255, 255, 255, 140), width=int(2 * scale))


def draw_bowl(draw, cx, cy, scale, color):
    draw.ellipse([cx - 110 * scale, cy - 30 * scale, cx + 110 * scale, cy + 90 * scale], fill=color)
    draw.ellipse([cx - 110 * scale, cy - 55 * scale, cx + 110 * scale, cy - 5 * scale], fill=(255, 255, 255, 235))
    import random
    random.seed(42)
    for _ in range(10):
        px = cx + random.randint(-80, 80) * scale
        py = cy - 30 * scale + random.randint(-8, 8) * scale
        r = 8 * scale
        draw.ellipse([px - r, py - r, px + r, py + r], fill=(180, 120, 60))


def draw_gear(draw, cx, cy, scale, color):
    import math
    r_out, r_in = 100 * scale, 65 * scale
    teeth = 8
    pts = []
    for i in range(teeth * 2):
        angle = math.pi * i / teeth
        r = r_out if i % 2 == 0 else r_in
        pts.append((cx + r * math.cos(angle), cy + r * math.sin(angle)))
    draw.polygon(pts, fill=color)
    draw.ellipse([cx - 35 * scale, cy - 35 * scale, cx + 35 * scale, cy + 35 * scale], fill=(255, 255, 255, 220))


MOTIFS = {"fish": draw_fish, "leaf": draw_leaf, "bowl": draw_bowl, "gear": draw_gear}


def make_image(slug, name, cat_slug):
    style = CATEGORY_STYLE[cat_slug]
    img = gradient_bg(style["start"], style["end"])
    draw = ImageDraw.Draw(img, "RGBA")

    motif_color = (255, 255, 255, 60)
    MOTIFS[style["motif"]](draw, W * 0.72, H * 0.38, 1.3, motif_color)

    overlay = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    odraw = ImageDraw.Draw(overlay)
    odraw.rectangle([0, H - 150, W, H], fill=(7, 59, 76, 150))
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
    draw = ImageDraw.Draw(img)

    font = ImageFont.truetype(FONT_BOLD, 46)
    bbox = draw.textbbox((0, 0), name, font=font)
    tw = bbox[2] - bbox[0]
    draw.text(((W - tw) / 2, H - 110), name, font=font, fill=(255, 255, 255))

    out_path = os.path.join(OUT_DIR, f"{slug}.webp")
    img.save(out_path, "WEBP", quality=82)
    print(f"wrote {out_path} ({os.path.getsize(out_path)} bytes)")


for slug, name, cat in PRODUCTS:
    make_image(slug, name, cat)
