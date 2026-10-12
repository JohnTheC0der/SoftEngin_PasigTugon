import sys
from predict import load_models, load_safety_keywords, apply_safety_net, analyze
from preprocess import clean

sector_model, priority_model = load_models()
safety_words = load_safety_keywords()

failures = 0


def check(name, condition):
    global failures
    if condition:
        print(f"PASS  {name}")
    else:
        failures += 1
        print(f"FAIL  {name}")


def result(text):
    return analyze(text, sector_model, priority_model, safety_words)


print("--- 1. Safety words must force High ---")
for text in [
    "May outbreak ng tigdas sa street namin",
    "A house is on fire and neighbors need help",
    "May sunog sa kabilang kanto",
    "Bumabaha na sa harap ng school",
    "May nagpaputok ng baril sa kanto",
]:
    r = result(text)
    check(f"{text!r} -> High with override", r["priority"] == "High" and r["safety_override"] is True)

print("\n--- 2. Whole words only: lookalike words must NOT trigger it ---")
for text in [
    "Ninakaw ang motor ko sa harap ng bahay",              # bahay is not baha
    "Nagsusunog ng basura ang kapitbahay namin",           # nagsusunog is not sunog
    "Fireworks are scaring the residents every night",     # fireworks is not fire
]:
    r = result(text)
    check(f"{text!r} -> no override", r["safety_override"] is False)

print("\n--- 3. The net only raises, never lowers ---")
check("High stays High when no safety word", apply_safety_net(clean("walang gamot sa health center"), "High", safety_words)[0] == "High")
check("Low stays Low when no safety word", apply_safety_net(clean("sira ang poste ng ilaw"), "Low", safety_words)[0] == "Low")
check("Low becomes High when a safety word is present", apply_safety_net(clean("may sunog sa kanto"), "Low", safety_words)[0] == "High")

print("\n--- 4. Output shape is always valid ---")
for text in [
    "Bumabaha na naman sa kalsada",
    "Maraming bata ang may lagnat",
    "Please repair the broken streetlight",
    "Ang sa ng na po",                                     # nothing left after cleaning
]:
    r = result(text)
    ok = (
        r["sector"] in {"Health", "Infrastructure", "Crime", "Environment"}
        and r["priority"] in {"High", "Medium", "Low"}
        and 0 <= r["confidence"] <= 1
        and isinstance(r["keywords"], str)
    )
    check(f"{text!r} -> valid sector, priority, confidence, keywords", ok)

print("\n--- 5. Safety words are listed first in keywords ---")
r = result("May outbreak ng dengue sa street namin")
check("keywords start with 'outbreak'", r["keywords"].split(", ")[0] == "outbreak")

print()
if failures:
    print(f"{failures} check(s) FAILED")
    sys.exit(1)
print("All checks passed")