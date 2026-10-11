import sys
import json
from pathlib import Path
import joblib
from preprocess import clean
from priority import get_priority

BASE_DIR = Path(__file__).parent
MODEL_PATH = BASE_DIR / "model.joblib"

def load_model():
    return joblib.load(MODEL_PATH)

def analyze(text, model):
    cleaned = clean(text)

    # 1. Sector: the model's probability for each sector, take the highest
    probabilities = model.predict_proba([cleaned])[0]
    best = probabilities.argmax()
    sector = str(model.classes_[best])
    confidence = round(float(probabilities[best]), 4)

    # 2. Priority: rule-based, from priority.py
    result = get_priority(text, sector)
    keywords = result["keywords"]

    # 3. No urgent keywords? Use up to 3 words the model recognizes
    if not keywords:
        vocab = model.named_steps["vec"].vocabulary_
        keywords = [w for w in dict.fromkeys(cleaned.split()) if w in vocab][:3]

    return {
        "sector": sector,
        "confidence": confidence,
        "priority": result["priority"],
        "keywords": ", ".join(keywords),
    }

def main():
    text = sys.argv[1].strip() if len(sys.argv) > 1 else ""
    if not text:
        print(json.dumps({"error": "No text provided."}))
        return 1

    try:
        model = load_model()
        output = analyze(text, model)
    except Exception as e:
        print(json.dumps({"error": f"Prediction failed: {e}"}))
        return 1

    print(json.dumps(output))
    return 0

if __name__ == "__main__":
    sys.exit(main())