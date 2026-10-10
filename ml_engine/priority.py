from preprocess import clean

# Urgency weight per keyword: 3 = High, 2 = Medium, 1 = Low.
# Whole-word matching, so word forms are listed explicitly.
URGENCY_WEIGHTS = {
    # 3 = High: immediate danger to life or property
    "sunog": 3, "nasusunog": 3, "fire": 3,
    "baha": 3, "bumabaha": 3, "nagbabaha": 3, "bumaha": 3, "flood": 3, "flooding": 3,
    "holdap": 3, "hinoldap": 3, "baril": 3, "pagnanakaw": 3,
    "outbreak": 3, "collapse": 3, "gumuho": 3, "bumigay": 3, "ambulansya": 3,
    # 2 = Medium: serious, but not an immediate emergency
    "nakaw": 2, "ninakaw": 2, "nanakawan": 2, "nagnanakaw": 2, "magnanakaw": 2, "snatcher": 2,
    "dengue": 2, "lagnat": 2, "nagrarambulan": 2,
}

PRIORITY_LABELS = {3: "High", 2: "Medium", 1: "Low"}

# Baseline when no urgent keyword is found: health and crime concerns start at Medium
SECTOR_BASE = {"Health": 2, "Crime": 2, "Infrastructure": 1, "Environment": 1}

def get_priority(text, sector):
    words = clean(text).split()                                  # Same cleaning as the model
    matched = [w for w in words if w in URGENCY_WEIGHTS]         # Keep only urgent words
    matched = list(dict.fromkeys(matched))                       # Remove duplicates, keep order
    weights = [SECTOR_BASE.get(sector, 1)] + [URGENCY_WEIGHTS[w] for w in matched]
    return {"priority": PRIORITY_LABELS[max(weights)], "keywords": matched}

if __name__ == "__main__":
    tests = [
        ("Nasusunog ang bahay ng kapitbahay namin", "Crime"),
        ("Ninakaw ang motor ko sa harap ng bahay", "Crime"),
        ("Barado ang kanal sa tapat ng bahay namin", "Infrastructure"),
        ("Bumabaha na naman sa kalsada", "Infrastructure"),
        ("Maraming bata ang may lagnat", "Health"),
    ]
    for text, sector in tests:
        print(text, "->", get_priority(text, sector))