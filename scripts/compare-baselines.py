from pathlib import Path
from PIL import Image, ImageChops
import json
old = Path('tests/__baseline-6.13.1__')
new = Path('tests/__baseline__')
rows = []
for path in sorted(old.glob('*.png')):
    other = new / path.name
    if not other.exists():
        rows.append({'file': path.name, 'status': 'missing-current'})
        continue
    a = Image.open(path).convert('RGBA')
    b = Image.open(other).convert('RGBA')
    if a.size != b.size:
        rows.append({'file': path.name, 'status': 'size-diff', 'old': a.size, 'new': b.size})
        continue
    diff = ImageChops.difference(a, b)
    bbox = diff.getbbox()
    pixels = a.width * a.height
    changed = sum(1 for pixel in diff.getdata() if pixel != (0, 0, 0, 0))
    rows.append({'file': path.name, 'status': 'ok', 'size': a.size, 'changed_pixels': changed, 'changed_percent': round(changed * 100 / pixels, 4), 'bbox': bbox})
print(json.dumps(rows, ensure_ascii=False, indent=2))
Path('rapport-baseline-historique-6.14.1.json').write_text(json.dumps(rows, ensure_ascii=False, indent=2) + '\n')
