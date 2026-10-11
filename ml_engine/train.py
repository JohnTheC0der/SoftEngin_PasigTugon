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

# Reads the CSV file
df = pd.read_csv(BASE_DIR / "complaints.csv")
df["clean"] = df["text"].apply(clean) # Runs clean() on every row of "text" and stores the results in a new "clean" column.

print("Total rows:", len(df))
print(df["sector"].value_counts()) # Counts how many rows each sector has.

# Splits the data: 75% to train, 25% hidden for testing
train_df, test_df = train_test_split(
    df, test_size=0.25, random_state=42, stratify=df["sector"]
)

# Builds and trains the model on the TRAINING part only
model = Pipeline([
    ("vec", TfidfVectorizer()),
    ("clf", MultinomialNB()),
])
model.fit(train_df["clean"], train_df["sector"])

# Tests it on the part it has never seen
predictions = model.predict(test_df["clean"])
print("\nAccuracy:", accuracy_score(test_df["sector"], predictions))
print(classification_report(test_df["sector"], predictions, zero_division=0))

# Shows the mistakes
for text, actual, predicted in zip(test_df["text"], test_df["sector"], predictions):
    if actual != predicted:
        print(f"WRONG: {text!r} | actual={actual} | predicted={predicted}")

# Cross-validation: a steadier accuracy estimate than a single split.
# 5 folds, each row is used as a test row exactly once. "Stratified" keeps sector proportions equal in every fold.
cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
scores = cross_val_score(model, df["clean"], df["sector"], cv=cv)
print(f"Cross-validation: mean={scores.mean():.2f}  folds={scores.round(2)}")

# The final model: retrain on ALL the data, then save
model.fit(df["clean"], df["sector"])
joblib.dump(model, BASE_DIR / "model.joblib")