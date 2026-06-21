# Kebab — wersja z panelem admina i mailami

Gałąź: `kebab`

## Jak wejść do panelu

Po wrzuceniu na hosting WWW z PHP:

```txt
https://twojadomena.pl/admin.php
```

Dane startowe:

```txt
login: admin
hasło: kebab2026!
```

## Co można zmieniać w panelu

- ceny produktów,
- nazwy produktów,
- kategorie,
- opisy,
- zdjęcia URL,
- aktywność produktu,
- rozmiary,
- mięsa/bazy,
- sosy,
- dodatki w środku,
- dodatki płatne,
- statusy zamówień.

## Mail do zamówień

Zamówienia są wysyłane na:

```txt
notingss@gmail.com
```

Konfiguracja jest w:

```txt
app-config.php
```

## Ważne

To nie zadziała na GitHub Pages, bo GitHub Pages nie uruchamia PHP. Trzeba wrzucić pliki na hosting WWW z obsługą PHP.

## Pliki do wrzucenia na hosting

Wgraj całą zawartość gałęzi `kebab` do katalogu strony, zwykle:

```txt
/public_html
```

Najważniejsze pliki:

```txt
index.html
style.css
script.js
admin.php
send-order.php
get-menu.php
app-config.php
data/menu.json
data/orders.json
```

## Gdy panel zapisuje, ale zmiany znikają

Sprawdź uprawnienia folderu:

```txt
data/
```

PHP musi móc zapisywać do:

```txt
data/menu.json
data/orders.json
```

## Gdy zamówienie zapisuje się, ale mail nie dochodzi

Wtedy hosting może blokować funkcję `mail()`. Następny krok to PHPMailer + SMTP.
