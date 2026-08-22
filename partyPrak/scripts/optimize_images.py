"""
One-off image optimization script (not part of the site runtime).

Resizes and recompresses images IN PLACE to match how large they actually
render on the site, so page weight matches the load-time budget. It never
touches an image the site does not reference, never changes a file's path,
and never makes a file bigger than it started (falls back to the original
bytes if re-encoding did not help). Safe to delete after running once.

Run:  python scripts/optimize_images.py
"""

import io
import os
import re
import sys
from PIL import Image, ImageOps

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
IMAGES = os.path.join(ROOT, 'images')

# (longest side in px, JPEG quality) — sized to how each image is actually
# displayed on the site, not to its original resolution.
BUCKETS = {
    # Logo shown inside a 44px circle — 132px covers 3x retina with room to spare.
    'images/brand/1.jpg': (132, 82),

    # Full-bleed hero background, but desaturated and darkened by a gradient
    # overlay — can be squeezed hard without a visible quality loss.
    'images/location/1.jpg': (1920, 74),

    # Avatars — 44-48px circles (hero trust strip, review cards).
    'images/people/1.jpg': (140, 80),
    'images/people/2.jpg': (140, 80),
    'images/people/4.jpg': (140, 80),
    'images/people/5.jpg': (140, 80),
    'images/people/6.jpg': (140, 80),
    'images/people/8.jpg': (140, 80),
    'images/people/9.jpg': (140, 80),

    # About-section collage — larger photos, ~700px and ~350px on screen.
    'images/people/3.jpg': (1100, 78),
    'images/people/7.jpg': (900, 78),
}

# Everything else here is used both as a ~450px card thumbnail and as a
# full-screen lightbox image, so it needs a larger ceiling.
DEFAULT_BUCKETS = [
    ('images/location/', (1400, 76)),
    ('images/plan/', (1400, 76)),
    ('images/promotions/', (1000, 78)),
]


def find_referenced_images():
    """Only touch files the site actually loads — never a dead asset."""
    pattern = re.compile(r'images/[\w./ -]+\.(?:jpg|jpeg|png)', re.IGNORECASE)
    found = set()
    for dirpath, dirs, files in os.walk(ROOT):
        dirs[:] = [d for d in dirs if d not in ('images', 'scripts', '.git')]
        for name in files:
            if name.endswith(('.html', '.js')):
                with open(os.path.join(dirpath, name), encoding='utf-8') as fh:
                    found.update(pattern.findall(fh.read()))
    return found


def bucket_for(rel_path):
    if rel_path in BUCKETS:
        return BUCKETS[rel_path]
    for prefix, bucket in DEFAULT_BUCKETS:
        if rel_path.startswith(prefix):
            return bucket
    return None


def optimize(path, max_side, quality):
    rel = os.path.relpath(path, ROOT).replace('\\', '/')
    ext = os.path.splitext(path)[1].lower()
    expected_formats = {'.jpg': 'JPEG', '.jpeg': 'JPEG', '.png': 'PNG'}

    with open(path, 'rb') as fh:
        original_bytes = fh.read()

    img = Image.open(io.BytesIO(original_bytes))

    # A handful of assets in this project are actually AVIF/WebP saved with
    # a .jpg/.png extension. Re-encoding those as JPEG/PNG would bloat them
    # (legacy formats are less efficient), so we leave format mismatches
    # completely untouched.
    if img.format != expected_formats.get(ext):
        print('%-32s skipped (real format is %s, not %s)' % (rel, img.format, expected_formats.get(ext)))
        return len(original_bytes), len(original_bytes)

    img = ImageOps.exif_transpose(img)

    resized = False
    w, h = img.size
    if max(w, h) > max_side:
        ratio = max_side / float(max(w, h))
        img = img.resize((max(1, round(w * ratio)), max(1, round(h * ratio))), Image.LANCZOS)
        resized = True

    buf = io.BytesIO()
    if ext == '.png':
        img.save(buf, format='PNG', optimize=True)
    else:
        if img.mode in ('RGBA', 'P'):
            img = img.convert('RGB')
        # Progressive JPEG adds scan-table overhead that only pays off past
        # a certain size, so skip it for the small avatar/logo bucket.
        img.save(buf, format='JPEG', quality=quality, optimize=True, progressive=max_side > 300)

    candidate = buf.getvalue()

    # Never make a file bigger than it started — keep the original bytes
    # whenever re-encoding did not actually help.
    if len(candidate) < len(original_bytes):
        with open(path, 'wb') as fh:
            fh.write(candidate)
        final = candidate
    else:
        final = original_bytes

    before, after = len(original_bytes), len(final)
    saved = before - after
    print('%-32s %6.0f KB -> %6.0f KB  (-%3.0f%%)%s' % (
        rel, before / 1024, after / 1024,
        100 * saved / before if before else 0,
        '  resized' if resized and final is candidate else ''
    ))
    return before, after


def main():
    referenced = find_referenced_images()
    total_before = 0
    total_after = 0
    touched = 0

    for rel in sorted(referenced):
        full = os.path.join(ROOT, rel)
        if not os.path.isfile(full):
            continue
        bucket = bucket_for(rel)
        if not bucket:
            continue
        max_side, quality = bucket
        b, a = optimize(full, max_side, quality)
        total_before += b
        total_after += a
        touched += 1

    print('-' * 70)
    print('Touched %d referenced files: %.1f MB -> %.1f MB (saved %.0f%%)' % (
        touched, total_before / 1024 / 1024, total_after / 1024 / 1024,
        100 * (total_before - total_after) / total_before if total_before else 0
    ))


if __name__ == '__main__':
    sys.exit(main())
