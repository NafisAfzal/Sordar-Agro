# Sordar Agro — GitHub Workflow (Group 05)

How the four of us push code without clashing. The golden rule:
**each person only ever pushes to their OWN branch.** Clashes only happen when two
people edit the same branch — so we never share a working branch.

Branches: `main` (protected, Nafis merges into it) + `nafis`, `mostahid`, `junaid`, `sayed`.

---

## STEP 0 — Nafis only: seed the repository (do this ONCE, first)

Nafis has the full working project. He pushes it to `main` so everyone has the
runnable skeleton to build on.

```bash
cd path/to/sordar-agro          # the project folder
git init
git add .
git commit -m "Initial project skeleton (Sordar Agro)"
git branch -M main
git remote add origin https://github.com/NAFIS_USERNAME/sordar-agro.git
git push -u origin main
```

Then create the four branches on GitHub (branch dropdown -> type name -> create
from main): `nafis`, `mostahid`, `junaid`, `sayed`.
Add the other three as collaborators: repo Settings -> Collaborators.
Protect `main`: Settings -> Branches -> require a pull request before merging.

---

## STEP 1 — Everyone: get the project on your machine (ONCE)

```bash
git clone https://github.com/NAFIS_USERNAME/sordar-agro.git
cd sordar-agro
git checkout YOUR_BRANCH        # nafis / mostahid / junaid / sayed
```

Then set it up locally (XAMPP/MySQL running):

```bash
composer install
copy .env.example .env          # Windows; use cp on Mac/Linux
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

---

## STEP 2 — Daily work loop (everyone, on your own branch)

Check TEAM-OWNERSHIP.md for which files are yours. Edit only those, then:

```bash
git checkout YOUR_BRANCH        # make sure you're on your branch
git add .
git commit -m "Describe what you did"
git push origin YOUR_BRANCH
```

That's it. Repeat as you work. Your commits land on your branch only.

---

## STEP 3 — Bringing work into `main` (Nafis, as lead)

When a teammate's part is ready, they open a **Pull Request** on GitHub from their
branch into `main`. Nafis reviews and merges it.

After merging, each person should refresh their branch so it includes the latest
`main` (prevents drift):

```bash
git checkout YOUR_BRANCH
git merge main                  # pull in the merged changes
git push origin YOUR_BRANCH
```

---

## If you ever hit a merge conflict
Don't panic. It means two branches changed the same lines (usually a shared file like
routes/web.php). Git marks the spots with `<<<<<<<` / `=======` / `>>>>>>>`.
Open the file, keep the correct lines, delete the markers, then:

```bash
git add the-conflicted-file
git commit
git push origin YOUR_BRANCH
```

To minimise this: only edit files listed as yours in TEAM-OWNERSHIP.md. Shared files
like routes/web.php are Nafis's responsibility.

---

## Quick reference: who is on which branch
| Person | Branch |
|--------|--------|
| Nafis (lead) | `nafis` + manages `main` |
| Mostahid | `mostahid` |
| Junaid | `junaid` |
| Sayed | `sayed` |
