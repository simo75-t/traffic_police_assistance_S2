"""STT normalization helpers.

This file contains Arabic-aware text normalization and rule-based cleanup helpers.
"""

import difflib
import re
from typing import Any, Optional


COMMON_FIXES = [
    ("إصطفاف", "اصطفاف"),
    ("اصطفاح", "اصطفاف"),
    ("شارة", "شارع"),
    ("السورة", "الثورة"),
    ("دماشق", "دمشق"),
    ("الدمشق", "دمشق"),
    ("محافظ دمشق", "دمشق"),
    ("محافظة دمشق", "دمشق"),
]

AR_NUM_WORDS = {
    "صفر": "0",
    "٠": "0",
    "واحد": "1",
    "واحدة": "1",
    "١": "1",
    "احد": "1",
    "اثنين": "2",
    "إثنين": "2",
    "اتنين": "2",
    "٢": "2",
    "ثلاثة": "3",
    "تلاتة": "3",
    "٣": "3",
    "أربعة": "4",
    "اربعة": "4",
    "٤": "4",
    "خمسة": "5",
    "خمسه": "5",
    "٥": "5",
    "ستة": "6",
    "سته": "6",
    "٦": "6",
    "سبعة": "7",
    "سبعه": "7",
    "٧": "7",
    "ثمانية": "8",
    "تمانية": "8",
    "٨": "8",
    "تسعة": "9",
    "تسعه": "9",
    "٩": "9",
}

AR_COLORS = {
    "احمر": "أحمر",
    "أحمر": "أحمر",
    "ازرق": "أزرق",
    "أزرق": "أزرق",
    "اخضر": "أخضر",
    "أخضر": "أخضر",
    "اسود": "أسود",
    "أسود": "أسود",
    "ابيض": "أبيض",
    "أبيض": "أبيض",
    "رمادي": "رمادي",
    "فضي": "فضي",
    "ذهبي": "ذهبي",
    "اصفر": "أصفر",
    "أصفر": "أصفر",
    "بنفسجي": "بنفسجي",
    "بني": "بني",
}

SY_CITIES_HINTS = [
    "دمشق",
    "حلب",
    "حمص",
    "حماة",
    "اللاذقية",
    "طرطوس",
    "درعا",
    "السويداء",
    "القنيطرة",
    "دير الزور",
    "الرقة",
    "الحسكة",
    "إدلب",
    "ريف دمشق",
]


def norm(value: Any) -> str:
    """Normalize spacing and apply common Arabic typo fixes."""
    if value is None:
        return ""
    text = str(value).strip()
    for src, target in COMMON_FIXES:
        text = text.replace(src, target)
    return re.sub(r"\s+", " ", text).strip()


def words_to_digits(text: str) -> str:
    """Convert Arabic number words into digits before extraction."""
    if not text:
        return ""
    out = text
    for word, digit in AR_NUM_WORDS.items():
        out = re.sub(rf"(?<!\S){re.escape(word)}(?!\S)", digit, out)
    return out


def normalize_plate(text: str) -> str:
    """Keep only numeric plate content after normalization."""
    return re.sub(r"[^0-9]", "", norm(words_to_digits(text)))


def best_plate_from_text(text: str) -> str:
    """Extract the most likely plate number from free text."""
    groups = re.findall(r"\d+", norm(words_to_digits(text)))
    if not groups:
        return ""
    joined = "".join(groups)
    return max(groups, key=len) if len(joined) > 12 else joined


def normalize_color(text: str) -> Optional[str]:
    """Map loose color mentions to one normalized Arabic color."""
    text = norm(text).lower()
    for source, color in AR_COLORS.items():
        if re.search(rf"\b{re.escape(source.lower())}\b", text):
            return color
    return None


def looks_like_street(text: str) -> bool:
    """Detect whether a value looks like a street field."""
    text = norm(text)
    return ("شارع" in text) or ("طريق" in text)


def extract_street_from_text(text: str) -> Optional[str]:
    """Extract the street phrase from raw transcript text."""
    value = norm(text)
    match = re.search(r"(شارع|طريق)\s+(.+)", value)
    if not match:
        return None
    street = norm(match.group(0))
    street = re.split(
        r"\b(أمام|مقابل|جنب|بالقرب|قرب|المخالفة|نوع المخالفة|المالك|لون|الموديل|المدينة|مدينة)\b",
        street,
    )[0].strip()
    return street or None


def clean_owner(text: str) -> Optional[str]:
    """Keep only the likely owner name fragment from mixed text."""
    value = norm(text)
    value = re.split(
        r"\b(نوع|لون|السيارة|المخالفة|شارع|طريق|المدينة|مدينة|أمام|مقابل|جنب|بالقرب|قرب)\b",
        value,
    )[0].strip()
    value = re.sub(r"[^\u0600-\u06FF\s]", "", value).strip()
    return " ".join(value.split()[:3]) if value else None


def clean_landmark(text: str) -> Optional[str]:
    """Keep only the location landmark fragment without violation terms."""
    value = norm(text)
    if not value:
        return None
    value = re.split(
        r"\b(المخالفة|نوع المخالفة|اصطفاف|تجاوز|إشارة|حزام|الهاتف)\b",
        value,
    )[0].strip()
    return value or None


def normalize_city_name(city: Optional[str]) -> Optional[str]:
    """Normalize city labels by removing generic prefixes."""
    if not city:
        return None
    value = norm(city)
    value = re.sub(r"\b(محافظة|محافظ|مدينة|منطقة|ريف)\b", "", value).strip()
    value = value.replace("الدمشق", "دمشق")
    return norm(value) or None


def guess_city_from_text(text: str) -> Optional[str]:
    """Infer the city directly from transcript text when the model misses it."""
    value = norm(text)
    for city in SY_CITIES_HINTS:
        if re.search(rf"\b{re.escape(city)}\b", value):
            return "دمشق" if city == "ريف دمشق" else city
    best = difflib.get_close_matches(value, SY_CITIES_HINTS, n=1, cutoff=0.60)
    if best:
        return "دمشق" if best[0] == "ريف دمشق" else best[0]
    return None
