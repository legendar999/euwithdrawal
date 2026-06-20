# EU Withdrawal — *Odstop od pogodbe* (PrestaShop 1.6 module)

A tiny, open‑source PrestaShop **1.6** module that adds the EU consumer
**"right of withdrawal" button** described by **Directive (EU) 2023/2673**
(the *withdrawal button*; SI transposition into ZVPot due **19 June 2026**).

It puts a clearly visible **withdrawal link** in the footer (and, optionally, the
header or a floating button) that opens a simple **two‑step** form. The two
clicks — *Continue* → *Confirm withdrawal* — match the "two‑click" model the
directive describes. On confirmation the module:

* stores the request in a back‑office **register** (`ps_euwithdrawal`),
* e‑mails the **customer** an acknowledgement of receipt, and
* e‑mails the **merchant** a notification.

No RMA system, no extra steps, nothing wired into the order workflow — by design.

---

## Features

| | |
|---|---|
| **Footer / header / floating** link | Toggle each placement independently. |
| **2‑step form** | Step 1: name, e‑mail, order number, (optional) date received, (optional) reason. Step 2: review + confirm. |
| **Order verification** | The order number is matched against the order **reference *or* numeric id**, and the **customer e‑mail must match** the order. |
| **Whole order or specific items** | Optional per‑line‑item selection on the confirm screen. |
| **Auto pre‑fill** | For logged‑in customers: name, e‑mail and a drop‑down of their orders. |
| **Acknowledgement e‑mails** | Customer + merchant, HTML and plain‑text. |
| **Back‑office register** | List, filter, status (*Received / Processing / Completed*), internal note, CSV export. |
| **Friendly URL** | Per‑language slug via `moduleRoutes` — e.g. `/en/withdrawal-from-contract`, `/si/odstop-od-pogodbe`, `/hr/odustanak-od-ugovora`, `/de/widerruf-des-vertrags`. Each slug is editable per language. |
| **i18n** | Ships native translations for **11 locales**: en, sl, si, hr, cs, hu, it, sk, de, fr, es (PrestaShop `$_MODULE` format). Link text, intro and slug default to native per language. |
| **Spam guard** | Honeypot field; e‑mails only ever go to the order's real customer address. |

## Requirements

* PrestaShop **1.6.0.0 – 1.6.1.x** (developed on 1.6.1.18, PHP 7.1+).
* A theme that renders the standard `displayFooter` / `displayTop` hooks
  (the default `default-bootstrap` theme does). If your theme hides the footer
  hook, enable the header or floating placement instead.

## Install

1. Copy the `euwithdrawal/` folder into your shop's `modules/` directory
   (or zip it and upload via **Modules → Add a new module**).
2. **Install** it from the Modules list.
3. Open **Configure** and set the placements, labels, intro text and merchant
   e‑mail. The public page URL is shown at the top of the config screen.

The back‑office register lives under **Orders → Withdrawals**.

## Configuration

* **Show link in footer / header / floating button** — where the link appears.
* **Link / page title** *(per language)* — the link text and page heading.
* **Intro text** *(per language)* — shown above the form.
* **Friendly URL slug** *(per language)* — e.g. `withdrawal-from-contract`.
* **Pre‑fill for logged‑in customers** — name/e‑mail + order drop‑down.
* **Allow withdrawing specific items** — whole‑order only if off.
* **Require login** — off by default (the directive expects open access).
* **E‑mail to customer / merchant** — toggle each; **Merchant e‑mail** override
  (blank = `PS_SHOP_EMAIL`).

## How order matching works

Shops number orders differently. The lookup matches the typed value against
**`reference`** *or* the numeric **`id_order`**, then requires the order's
**customer e‑mail** to match. So both `000007626` (a zero‑padded reference) and
`7626` (the id) resolve to the same order — no configuration needed.

## Data & privacy

Each request stores name, e‑mail, order reference, optional date/reason, the
selected items, a status, an internal note, the client IP and timestamps.
**Uninstalling drops the `ps_euwithdrawal` table** — export it first if you must
keep the records (a legal retention obligation may apply). You are the data
controller for this data and are responsible for handling it lawfully (e.g. GDPR).

## ⚠️ Disclaimer & limitation of liability

**This module is a technical tool, not legal advice, and it does not by itself
make your shop legally compliant.**

* The software is provided **"AS IS", without any warranty** of any kind, express
  or implied (see the [LICENSE](LICENSE)). Use it at your own risk.
* **Legal compliance is solely the responsibility of the merchant / website
  owner.** Whether your shop satisfies Directive (EU) 2023/2673, your national
  transposition (e.g. ZVPot in Slovenia), GDPR, or any other law — including the
  exact wording, placement, withdrawal form, deadlines, record‑keeping and the
  handling of submitted requests — is **your** responsibility to verify and
  ensure, ideally with your own qualified legal counsel.
* The author and contributors **accept no responsibility or liability** whatsoever
  for any non‑compliance, regulatory action, fine, penalty, dispute, loss of data,
  business interruption, or any direct, indirect or consequential damages arising
  from the installation, configuration, use, misuse or malfunction of this module.
* You are responsible for reviewing the code, testing the module on your own shop,
  configuring it correctly, monitoring and answering the requests it records, and
  keeping it up to date with the applicable law. Laws change — verify the current,
  authoritative text yourself.

If you do not accept these terms, do not install or use the module.

## License

[Academic Free License 3.0](LICENSE) — © Andriy Gryban. PRs welcome.

<br>

---
---

# 🇸🇮 Slovenščina

Majhen, **odprtokoden** modul za PrestaShop **1.6**, ki doda potrošniški
**gumb „Odstop od pogodbe"** iz **Direktive (EU) 2023/2673** (rok za prenos v
slovenski ZVPot: **19. junij 2026**).

V nogo (po želji tudi v glavo ali kot lebdeči gumb) postavi **jasno vidno
povezavo** do preprostega **dvostopenjskega** obrazca. Dva klika —
*Nadaljuj* → *Potrdi odstop* — ustrezata „dvokličnemu" modelu iz direktive. Po
potrditvi modul:

* shrani zahtevek v **evidenco** v administraciji (`ps_euwithdrawal`),
* pošlje **stranki** samodejno potrdilo o prejemu in
* pošlje **trgovcu** obvestilo.

Brez RMA sistema, brez dodatnih korakov, ne posega v obstoječi sistem naročil.

## Funkcije

| | |
|---|---|
| **Noga / glava / lebdeči** gumb | Vsako mesto se vklopi neodvisno. |
| **2‑stopenjski obrazec** | 1. korak: ime, e‑naslov, številka naročila, (neobvezno) datum prejema, (neobvezno) razlog. 2. korak: pregled + potrditev. |
| **Preverjanje naročila** | Številka se ujema z **referenco *ali* številko naročila**, **e‑naslov stranke pa se mora ujemati** z naročilom. |
| **Celo naročilo ali posamezni izdelki** | Neobvezna izbira posameznih postavk na zaslonu za potrditev. |
| **Samodejno predizpolnjevanje** | Za prijavljene stranke: ime, e‑naslov in spustni seznam njihovih naročil. |
| **Potrdila po e‑pošti** | Stranki + trgovcu, HTML in golo besedilo. |
| **Evidenca v administraciji** | Seznam, filtri, status (*Prejet / V obdelavi / Zaključen*), interna opomba, izvoz CSV. |
| **Prijazni URL** | Sluga na jezik prek `moduleRoutes` — npr. `/si/odstop-od-pogodbe`, `/en/withdrawal-from-contract`. Vsak slug je urejljiv po jeziku. |
| **Večjezičnost** | Domači prevodi za **11 jezikov**: en, sl, si, hr, cs, hu, it, sk, de, fr, es. Besedilo povezave, uvod in slug so privzeto v domačem jeziku. |
| **Zaščita pred spamom** | Skrito polje (honeypot); e‑pošta gre vedno le na pravi e‑naslov stranke iz naročila. |

## Zahteve

* PrestaShop **1.6.0.0 – 1.6.1.x** (razvito na 1.6.1.18, PHP 7.1+).
* Tema, ki izriše standardna hooka `displayFooter` / `displayTop` (privzeta
  `default-bootstrap` to počne). Če tema skriva nogo, vklopi glavo ali lebdeči gumb.

## Namestitev

1. Kopiraj mapo `euwithdrawal/` v `modules/` svoje trgovine (ali jo zapakiraj v
   ZIP in naloži prek **Moduli → Dodaj nov modul**).
2. **Namesti** ga s seznama modulov.
3. Odpri **Konfiguracija** in nastavi mesta, oznake, uvodno besedilo in e‑naslov
   trgovca. Javni URL strani je prikazan na vrhu konfiguracije.

Evidenca je v administraciji pod **Naročila → Withdrawals**.

## Konfiguracija

* **Prikaz v nogi / glavi / lebdeči gumb** — kje se povezava prikaže.
* **Naslov povezave / strani** *(po jeziku)* — besedilo povezave in naslov strani.
* **Uvodno besedilo** *(po jeziku)* — prikazano nad obrazcem.
* **Slug prijaznega URL‑ja** *(po jeziku)*.
* **Predizpolni za prijavljene stranke** — ime/e‑naslov + spustni seznam naročil.
* **Dovoli odstop od posameznih izdelkov** — sicer le celo naročilo.
* **Zahtevaj prijavo** — privzeto izklopljeno (direktiva pričakuje prost dostop).
* **E‑pošta stranki / trgovcu** — vsako posebej; **E‑naslov trgovca** (prazno =
  `PS_SHOP_EMAIL`).

## ⚠️ Zavrnitev odgovornosti

**Ta modul je tehnično orodje, ni pravni nasvet in sam po sebi vaše trgovine ne
naredi pravno skladne.**

* Programska oprema je na voljo **„TAKŠNA, KOT JE", brez kakršnega koli jamstva**
  (glej [LICENSE](LICENSE)). Uporaba je na lastno odgovornost.
* **Za pravno skladnost je odgovoren izključno trgovec / lastnik spletne strani.**
  Ali vaša trgovina izpolnjuje Direktivo (EU) 2023/2673, njen nacionalni prenos
  (npr. ZVPot v Sloveniji), GDPR ali katero koli drugo zakonodajo — vključno z
  natančnim besedilom, umestitvijo, obrazcem za odstop, roki, vodenjem evidence in
  obravnavo prejetih zahtevkov — morate preveriti in zagotoviti **sami**, najbolje
  s svojim pravnim svetovalcem.
* Avtor in soavtorji **ne prevzemajo nobene odgovornosti** za morebitno
  neskladnost, ukrepe nadzornih organov, globe, kazni, spore, izgubo podatkov,
  prekinitev poslovanja ali kakršno koli neposredno, posredno ali posledično
  škodo, ki bi nastala zaradi namestitve, konfiguracije, uporabe, napačne uporabe
  ali nedelovanja tega modula.
* Sami ste odgovorni za pregled kode, testiranje modula na svoji trgovini, pravilno
  konfiguracijo, spremljanje in odgovarjanje na zabeležene zahtevke ter za
  posodabljanje skladno z veljavno zakonodajo. Zakoni se spreminjajo — veljavno
  besedilo preverite sami.

Če teh pogojev ne sprejemate, modula ne nameščajte in ne uporabljajte.

## Licenca

[Academic Free License 3.0](LICENSE) — © Andriy Gryban. Prispevki (PR) dobrodošli.
