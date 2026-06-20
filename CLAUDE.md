# CLAUDE.md — `euwithdrawal` module build brief

Binding brief for any Claude/agent working on this module. Read it before
touching code. Keep it current.

> **PUBLIC REPO.** This folder is published open‑source. **Never commit secrets**
> (FTP/BO passwords, API keys). Test/prod credentials live in the workspace
> `../.env` (one level up, outside this repo) — reference them, never inline them.

## What this is

An **ultra‑simple**, vendor‑neutral PrestaShop **1.6** module implementing the EU
consumer **withdrawal button** (Directive (EU) 2023/2673; SI ZVPot, due
2026‑06‑19). Built for **akvavent.si** (PS 1.6.1.16) but written to be generic
and open‑source.

User‑facing flow (one front controller, three rendered states):

```
footer/header/floating link  →  /odstop-od-pogodbe
   STEP 1  form     (name, e‑mail, order #, [date], [reason])   → "Continue"
   STEP 2  review   (verified order + item picker)              → "Confirm withdrawal"
   STEP 3  done     (acknowledgement; e‑mails sent; row stored)
```

## Architecture / file map

```
euwithdrawal.php                         Module class `Euwithdrawal`: install/uninstall,
                                         hooks (displayHeader/Footer/Top, moduleRoutes),
                                         config form (getContent), Tab register, helpers.
classes/EuWithdrawalRequest.php          ObjectModel for one request (table `euwithdrawal`).
controllers/front/withdrawal.php         `EuwithdrawalWithdrawalModuleFrontController`
                                         — the 3‑step flow, order lookup, mail, persist.
controllers/admin/AdminEuWithdrawalController.php
                                         BO register: list + status + note + detail panel.
sql/install.php, sql/uninstall.php       `$sql[]` arrays run by Euwithdrawal::runSqlFile().
views/templates/front/{form,confirm,done}.tpl   the 3 states.
views/templates/hook/{footer_link,floating_button}.tpl
views/templates/admin/config_info.tpl    info panel above the config form.
views/css/front.css, views/js/front.js   styling + scope‑radio toggle.
mails/{en,sl}/withdrawal_{customer,merchant}.{html,txt}   transactional e‑mails.
translations/{en,sl}.php                 module `$_MODULE` strings (generated, see below).
```

## PS 1.6 facts this relies on (verified against 1.6.1.16 source)

* **Front controller class name** is resolved **case‑insensitively** by
  `Dispatcher.php:295` as `$module.$controller.'ModuleFrontController'`. The file
  is `include_once`'d, so module classes are **not autoloaded** → we
  `require_once` `EuWithdrawalRequest.php` in both the module and the admin
  controller.
* **Friendly URL** `/odstop-od-pogodbe` comes from `hookModuleRoutes`; the route
  key must be `module-euwithdrawal-withdrawal` so `Link::getModuleLink()` finds
  it (`Link.php:363`). Slug is configurable (`EUWITHDRAWAL_SLUG`).
* **Footer** = register hook `displayFooter`, return HTML from `hookDisplayFooter`.
  **Header link** = `displayTop` (visible). `displayHeader` is for CSS/JS only.
* **Order lookup**: `reference` OR `id_order`, **AND** customer e‑mail (joined on
  `customer.id_customer`). akvavent.si uses a **zero‑padded reference == id**
  (`000007626` for id 7626), so matching both forms is required.
* **Mail::Send** 15‑arg signature; module templates via
  `templatePath = _PS_MODULE_DIR_.'euwithdrawal/mails/'`, files `mails/{iso}/X.{html,txt}`
  (**both** html+txt must exist). `mailLang()` falls back to `en` when the
  order's language has no template.
* **Nullable DATE pitfall**: ObjectModel writes `0000-00-00` for empty `TYPE_DATE`,
  which fails under MySQL strict mode. `date_received` is therefore `varchar(10)`
  (`isDateFormat`), storing an ISO date or `''`.
* **BO list `callback`** runs on the **controller** (`HelperList.php:320`), so
  `renderStatusBadge`/`renderScope` are controller methods returning HTML.
* **`copyFromPost`** only copies **posted** fields (`AdminController.php:3555`), so
  the edit form (status + note only) does not wipe the required name/e‑mail.
* **Translations**: FO templates use `getModuleTranslation` with `source =`
  template basename; the front controller uses `source = 'withdrawal'`; status
  labels use `source = 'euwithdrawalrequest'`; the config page uses
  `source = 'euwithdrawal'`. **AdminController `$this->l()` does NOT use the module
  file** — it routes to `getAdminTranslation` (core admin pack), so BO strings are
  auto‑translated where the core pack has them and fall back to English otherwise.

## Translations & per-language slugs

Ships **11 locales**: en, sl, si, hr, cs, hu, it, sk, de, fr, es. The
`translations/{iso}.php` files are **generated** by `../gen_i18n.php` (workspace
root, not shipped) which mirrors PS's md5 key derivation exactly. Source strings
live in `gen_i18n.php`: `$rows` = [source, en, sl]; `$EN` = the canonical ordered
English list; `$T[iso]` = per-language arrays in the SAME order as `$EN`. To
add/edit: update those, run `"C:/xampp/php/php.exe" gen_i18n.php`, commit. EN must
match the in-code string byte-for-byte.

Per-language **link label / intro / slug** defaults live in
`Euwithdrawal::i18nDefaults($iso)` (PHP). `EUWITHDRAWAL_LINK_LABEL`, `_INTRO` and
`_SLUG` are all **multilang** config. Friendly URLs are per-language:
`hookModuleRoutes` registers one route per language (`module-euwithdrawal-withdrawal-{id_lang}`
+ a primary), and `getWithdrawalLink($id_lang)` builds `getBaseLink()+iso/+slug`
manually (PS 1.6 applies one route rule across all langs, so getModuleLink can't do
per-lang slugs). The FO controller's form action uses `$this->module->getWithdrawalLink()`.
NOTE: `Link::getLangLink()` is protected — we replicate its `iso/` logic.

## Config keys (`Configuration`)

`EUWITHDRAWAL_SHOW_FOOTER|SHOW_HEADER|SHOW_FLOATING|PREFILL|ALLOW_ITEMS|`
`REQUIRE_LOGIN|NOTIFY_CUSTOMER|NOTIFY_MERCHANT` (bool),
`EUWITHDRAWAL_MERCHANT_EMAIL`, `EUWITHDRAWAL_SLUG`,
`EUWITHDRAWAL_LINK_LABEL` (multilang), `EUWITHDRAWAL_INTRO` (multilang).
NOTE: `form.tpl` outputs the intro via `{$euw_intro|escape:'html':'UTF-8'|nl2br}`
(escaped text + line breaks, **not raw HTML**) — required by the PrestaShop Addons
validator, which rejects `nofilter`. Don't put HTML markup in the intro; it renders
as text.

## Build / lint / deploy / test

* **Lint**: `for f in $(find euwithdrawal -name '*.php'); do "C:/xampp/php/php.exe" -l "$f"; done`
* **Coding standards** (Addons validator parity): php-cs-fixer with `@Symfony` +
  `concat_space => one` + `cast_spaces => single` (the PrestaShop standalone ruleset;
  NO `header_comment` — license tags are checked separately, and `@Symfony` requires
  the blank line after `<?php` that `header_comment` would suppress). Tooling lives
  outside the repo at `C:/Users/andig/.pscs-tools/` (`.php-cs-fixer-test.php` = the
  real config). License headers must be `@author`, `@copyright`, `@license` **in that
  order** in every PHP file. Translations are excluded from php-cs-fixer; their only CS
  fix (`$_MODULE = [];`) comes from `../gen_i18n.php`. Distributable zip:
  `git archive --format=zip --prefix=euwithdrawal/ HEAD -o euwithdrawal-<ver>.zip`
  (root folder MUST be `euwithdrawal/`; dev files are `export-ignore`'d in `.gitattributes`).
* **Reference PS 1.6 source**: `../prestashop/` (read‑only).
* **Test site** (PS 1.6.1.18, default‑bootstrap): `oldakvavent.gribanica.eu` —
  deploy via plain **FTP**, install/configure via **BO**. Creds in `../.env`
  (`TESTING_*`). Had an `http↔https` canonical loop (Hostinger forces https, PS
  canonical pointed to http) — broke it with `PS_CANONICAL_REDIRECT=0`.
* **Production** akvavent.si (PS 1.6.1.18, default‑bootstrap, friendly URLs ON):
  - **FTPS required** — `curl --ssl-reqd` (plain FTP returns "550 SSL/TLS required").
  - Web root `public_html/`, modules `public_html/modules/`, admin `admin407ed3f51`.
  - **Slovenian uses iso_code `si`, not `sl`** (langs: si,en,hr,cs,hu,it,sk). PS loads
    `translations/si.php`; install defaults key off `si`. The module ships BOTH
    `sl.php` and `si.php` and treats both isos as Slovenian (`defaultLabel`/`defaultIntro`).
  - Orders use a zero‑padded reference == id (`000007626`). Friendly URL is
    `/si/odstop-od-pogodbe` (lang prefix + moduleRoutes).
  - Install via BO module list (no bypass scripts on the live site).
* **`Db::getRow()`/`getValue()` auto‑append `LIMIT 1`** — never put `LIMIT 1` in a
  query passed to them (findOrder bug, fixed: `LIMIT 1 LIMIT 1` = SQL syntax error).

## Conventions

* PHP: `array()` syntax, PS house style, guard every file with
  `if (!defined('_PS_VERSION_')) exit;`, `index.php` stub in every folder.
* Escape all output in `.tpl` (`|escape:'html':'UTF-8'`) except `{l}` (already
  escaped) and the admin‑built detail HTML (escaped in PHP).
* Security: order lookup is re‑run at confirm against the posted e‑mail (hidden
  ids are never trusted); honeypot `euw_website`; e‑mails only to the order's
  customer + the configured merchant.

## Status

* **v1.1.1** (2026‑06‑20) — **PrestaShop Addons validator pass**. Fixed: config.xml
  `<description>` now matches `$this->description`; `@copyright` tag added (correct
  `@author`/`@copyright`/`@license` order) to every PHP file; root `.htaccess`
  (`Options -Indexes`); `form.tpl` intro escaped (no more `nofilter`); all `@Symfony`
  CS deviations auto-fixed (short arrays, concat spacing, blank line after `<?php`,
  etc.); `$_MODULE = [];` in all 11 translations (via gen_i18n.php). Distributable zip
  must use `--prefix=euwithdrawal/`. See "Coding standards" above.
* **v1.0.0** — built & **LIVE on akvavent.si** 2026‑06‑20. Also fully tested
  end‑to‑end on the test site (install, FO 3‑step flow, both e‑mails, BO register,
  negative + honeypot). PHP‑lint clean; adversarial review (17 agents) = 0 confirmed
  serious issues.
* Production verified: `/si/odstop-od-pogodbe` (Slovenian), footer link, BO register,
  bogus‑lookup rejected. A REAL withdrawal was **not** submitted on prod (would
  e‑mail a real customer) — that path is proven on the test site only.
* Merchant notifications go to `PS_SHOP_EMAIL` unless `EUWITHDRAWAL_MERCHANT_EMAIL`
  is set in BO config — confirm the desired address with the owner.
* Open: per‑language route slug; optional GDPR‑safe "keep table on uninstall" toggle;
  translations for cs/hu/sk/it/hr (currently fall back to English).
