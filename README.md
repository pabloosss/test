# Cyberforma — strona reklamowa

Profesjonalna strona reklamowa dla osoby tworzącej strony internetowe na zamówienie.

## Branch roboczy

Pracujemy wyłącznie na branchu:

```text
moja-strona
```

## Cel strony

Strona ma reklamować usługę tworzenia stron internetowych dla małych firm. Ma wyglądać profesjonalnie, jasno tłumaczyć ofertę i prowadzić klienta do kontaktu.

## Obecna wersja

Aktualna wersja zawiera:

- stronę główną Cyberforma
- ofertę usług
- cennik startowy
- proces współpracy
- telefon i e-mail
- formularz zapytania o stronę
- jedno dostępne demo: kebab / restauracja

## Dostępne demo

```text
demo/kebab/
```

Na razie na stronie głównej pokazujemy tylko demo kebaba. Kolejne demo branżowe dodamy później, np. mechanik, beauty, transport albo kwiaciarnia.

## Formularz kontaktowy pyta o

- imię i nazwisko
- firmę / branżę
- telefon
- e-mail
- typ strony
- budżet
- termin
- oczekiwane elementy strony
- opis projektu

## Ważne dane do podmiany

W plikach `index.html` i `script.js` trzeba zmienić dane tymczasowe:

```text
+48 000 000 000
```

Mail jest ustawiony jako:

```text
kontakt@cyberforma.pl
```

## Formularz

Aktualnie formularz działa przez `mailto:`. To oznacza, że po kliknięciu przygotowuje gotową wiadomość e-mail w programie pocztowym użytkownika.

Później można podpiąć prawdziwą obsługę formularza przez backend albo zewnętrzną usługę formularzy.
