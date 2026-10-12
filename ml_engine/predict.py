import sys
import json
from pathlib import Path
import joblib
from preprocess import clean

BASE_DIR = Path(__file__).parent


def load_models():
    # Two saved models, both created by train.py
    sector_model = joblib.load(BASE_DIR / "sector_model.joblib")
    priority_model = joblib.load(BASE_DIR / "priority_model.joblib")
    return sector_model, priority_model


def predict_label(model, cleaned):
    # predict_proba gives one probability per label, e.g. [0.12, 0.05, 0.71, 0.12]
    probabilities = model.predict_proba([cleaned])[0]
    best = probabilities.argmax()                       # position of the biggest probability
    label = str(model.classes_[best])                   # classes_ holds the label names in the same order
    return label, round(float(probabilities[best]), 4)


def top_keywords(model, cleaned, n=3):
    # Picks the words in this complaint with the highest TF-IDF weight (the most distinctive ones).
    vectorizer = model.named_steps["vec"]
    row = vectorizer.transform([cleaned])               # the complaint as TF-IDF numbers
    names = vectorizer.get_feature_names_out()          # every word the model knows
    ranked = sorted(zip(row.indices, row.data), key=lambda pair: pair[1], reverse=True)
    return [str(names[i]) for i, _ in ranked[:n]]


def analyze(text, sector_model, priority_model):
    cleaned = clean(text)
    sector, confidence = predict_label(sector_model, cleaned)
    priority, priority_confidence = predict_label(priority_model, cleaned)
    keywords = top_keywords(sector_model, cleaned)
    return {
        "sector": sector,
        "confidence": confidence,
        "priority": priority,
        "priority_confidence": priority_confidence,
        "keywords": ", ".join(keywords),
    }


def main():
    text = sys.argv[1].strip() if len(sys.argv) > 1 else ""
    if not text:
        print(json.dumps({"error": "No text provided."}))
        return 1

    try:
        sector_model, priority_model = load_models()
        output = analyze(text, sector_model, priority_model)
    except Exception as e:
        print(json.dumps({"error": f"Prediction failed: {e}"}))
        return 1

    print(json.dumps(output))
    return 0


if __name__ == "__main__":
    sys.exit(main())