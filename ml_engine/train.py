import joblib
import re
import pandas as pd # Handles the CSV Files
from sklearn.feature_extraction.text import TfidfVectorizer # Converts the text into numbers
from sklearn.naive_bayes import MultinomialNB # Naive Bayes
from sklearn.pipeline import Pipeline # Chains steps together to run them as one.

# Stuff that the algo is gonna bypass to focus on more important words. I think. Add more to train the algo
STOPWORDS = {"ang", "sa", "ng", "na", "po", "ako", "the", "is", "at", "mga",
             "dito", "namin", "ko", "may", "ay", "amin", "aming", "lugar", "street"} # NEW WORDS ADDED AFTER FIRST TEST "amin, aming, lugar, street"

def clean(text):
    text = text.lower() # Turns the text lowercase
    text = re.sub(r"[^a-z0-9\s]", " ", text) # Replaces special characters into spaces
    return " ".join(w for w in text.split()
                    if w not in STOPWORDS) # text.split chops the strings into a list of words, "if w not in STOPWORDS" ensures it only keeps words that aren't in the STOPWORDS, ".join" glues the list back into one string.

df = pd.read_csv("complaints.csv") # Reads the CSV file
df["clean"] = df["text"].apply(clean) # df is a spreadsheet in memory with the columns text and sector df["text"] selects one column. .apply(clean) runs the "clean" function for every row of the column, df["clean"] makes a new column called "clean" and stores the results in that column.

print(df[["text", "clean"]].head()) # Displays the first 5 rows

model = Pipeline([
    ("vec", TfidfVectorizer()),
    ("clf", MultinomialNB()),
])
model.fit(df["clean"], df["sector"])
joblib.dump(model, "model.joblib")

tests = ["may holdap sa kanto", "baha na naman sa kalsada", "maraming basura sa ilog"]
for t in tests:
    print(t, "->", model.predict([clean(t)])[0])