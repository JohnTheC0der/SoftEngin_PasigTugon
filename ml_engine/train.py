from pathlib import Path
import joblib
import pandas as pd # Handles the CSV Files
from sklearn.feature_extraction.text import TfidfVectorizer # Converts the text into numbers
from sklearn.naive_bayes import MultinomialNB # Naive Bayes
from sklearn.pipeline import Pipeline # Chains steps together to run them as one.
from sklearn.model_selection import train_test_split, cross_val_score, StratifiedKFold
from sklearn.metrics import accuracy_score, classification_report
from preprocess import clean

BASE_DIR = Path(__file__).parent # The folder this script lives in (ml_engine)

# Reads the CSV file. It now has THREE columns: text, sector, priority
df = pd.read_csv(BASE_DIR / "complaints.csv")

# Stops early with a clear message if the CSV is missing a column or has empty cells
for column in ["text", "sector", "priority"]:
    if column not in df.columns:
        raise SystemExit(f"complaints.csv is missing the '{column}' column.")
if df[["text", "sector", "priority"]].isnull().any().any():
    raise SystemExit("complaints.csv has empty cells. Fill or remove those rows first.")

df["clean"] = df["text"].apply(clean) # Runs clean() on every complaint and stores the result in a new column.

print("Total rows:", len(df))
print(df["sector"].value_counts())
print(df["priority"].value_counts())


def build_model():
    # The same recipe is used for BOTH models: numbers from text, then Naive Bayes.
    return Pipeline([
        ("vec", TfidfVectorizer()),
        ("clf", MultinomialNB()),
    ])


def train_and_save(target, filename):
    """Trains one model that predicts the column named `target`, measures it, and saves it."""
    print(f"\n==================== {target.upper()} MODEL ====================")

    # Splits the data: 75% to train, 25% hidden for testing
    train_df, test_df = train_test_split(
        df, test_size=0.25, random_state=42, stratify=df[target]
    )

    # Trains on the TRAINING part only, then tests on the part it has never seen
    model = build_model()
    model.fit(train_df["clean"], train_df[target])
    predictions = model.predict(test_df["clean"])
    print("Accuracy:", round(accuracy_score(test_df[target], predictions), 3))
    print(classification_report(test_df[target], predictions, zero_division=0))

    # Shows the first few mistakes so you can study them
    mistakes = [
        (text, actual, predicted)
        for text, actual, predicted in zip(test_df["text"], test_df[target], predictions)
        if actual != predicted
    ]
    for text, actual, predicted in mistakes[:8]:
        print(f"WRONG: {text!r} | actual={actual} | predicted={predicted}")
    if len(mistakes) > 8:
        print(f"... and {len(mistakes) - 8} more mistakes not shown")

    # Cross-validation: a steadier accuracy estimate than one split.
    # 5 folds, every row is used as a test row exactly once.
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
    scores = cross_val_score(build_model(), df["clean"], df[target], cv=cv)
    print(f"Cross-validation: mean={scores.mean():.2f}  folds={scores.round(2)}")

    # The final model: retrain on ALL the data, then save it
    final_model = build_model()
    final_model.fit(df["clean"], df[target])
    joblib.dump(final_model, BASE_DIR / filename)
    print(f"Saved -> {filename}")


# One function, called twice: once for the sector, once for the priority
train_and_save("sector", "sector_model.joblib")
train_and_save("priority", "priority_model.joblib")