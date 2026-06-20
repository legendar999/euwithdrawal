# EU Withdrawal — *Odstop od pogodbe* (PrestaShop 1.6 module)

A tiny, open‑source PrestaShop **1.6** module that adds the EU consumer
**"right of withdrawal" button** required by **Directive (EU) 2023/2673**
(the *withdrawal button* / *gumb za odstop od pogodbe*; SI transposition into
ZVPot due **19 June 2026**).

It puts a clearly visible **"Odstop od pogodbe"** link in the footer (and,
optionally, the header or a floating button) that opens a simple **two‑step**
form. The two clicks — *Continue* → *Confirm withdrawal* — are exactly the
"two‑click" model the directive describes. On confirmation the module:

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
| **Acknowledgement e‑mails** | Customer + merchant, HTML and plain‑text, EN + SL templates. |
| **Back‑office register** | List, filter, status (*Received / Processing / Completed*), internal note, CSV export. |
| **Friendly URL** | Per‑language slug via `moduleRoutes` — e.g. `/si/odstop-od-pogodbe`, `/en/withdrawal-from-contract`, `/hr/odustanak-od-ugovora`, `/de/widerruf-des-vertrags`. Each slug is editable per language in the config. |
| **i18n** | Ships native translations for **11 locales**: en, sl, si, hr, cs, hu, it, sk, de, fr, es (PrestaShop `$_MODULE` format, official EU consumer‑law terminology). Link text, intro and slug default to native per language. |
| **Spam guard** | Honeypot field; e‑mails only ever go to the order's real customer address. |

## Requirements

* PrestaShop **1.6.0.0 – 1.6.1.x** (developed on 1.6.1.16, PHP 7.1+).
* A theme that renders the standard `displayFooter` / `displayTop` hooks
  (the default `default-bootstrap` theme does).

## Install

1. Copy the `euwithdrawal/` folder into your shop's `modules/` directory
   (or zip it and upload via **Modules → Add a new module**).
2. **Install** it from the Modules list.
3. Open **Configure** and set the placements, labels, intro text and merchant
   e‑mail. The public page URL is shown at the top of the config screen.

The back‑office register lives under **Orders → Odstop od pogodbe / Withdrawals**.

## Configuration

* **Show link in footer / header / floating button** — where the link appears.
* **Link / page title** *(per language)* — the link text and page heading.
* **Intro text** *(per language)* — shown above the form.
* **Friendly URL slug** — default `odstop-od-pogodbe`.
* **Pre‑fill for logged‑in customers** — name/e‑mail + order drop‑down.
* **Allow withdrawing specific items** — whole‑order only if off.
* **Require login** — off by default (the law expects open access).
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
keep the records (a legal retention obligation may apply).

## Pravna opomba (SL)

Modul doda **jasno viden gumb "Odstop od pogodbe"** in preprost dvostopenjski
obrazec (dva klika: *Nadaljuj* → *Potrdi odstop*), kar ustreza zahtevam
**Direktive (EU) 2023/2673**. Po oddaji stranka prejme **samodejno potrdilo o
prejemu** zahtevka, trgovec pa obvestilo; vsi zahtevki se vodijo v **evidenci**
v administraciji. Modul ne posega v obstoječi sistem naročil. Ta modul je
tehnično orodje in **ne predstavlja pravnega nasveta** — za skladnost preverite
veljavno besedilo zakona.

## License

[Academic Free License 3.0](LICENSE) — © Andriy Gryban. PRs welcome.
