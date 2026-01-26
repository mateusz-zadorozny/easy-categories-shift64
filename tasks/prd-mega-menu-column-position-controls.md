# PRD: Mega Menu Column Position Controls

## Overview
Rozszerzenie pluginu Easy Categories Shift64 o możliwość szybkiego ustawiania pozycji kolumn mega menu (left/right/not set) bezpośrednio z widoku drzewa kategorii. Funkcjonalność wykorzystuje istniejące pole `merida_mega_menu_column_position` z motywu Kadence Child i pozwala na natychmiastową zmianę wartości poprzez kliknięcie przycisków przy każdej kategorii.

## Goals
- Umożliwić szybką zmianę pozycji mega menu dla dowolnej kategorii bez opuszczania widoku drzewa
- Zapewnić natychmiastowy zapis zmian (AJAX) bez przeładowania strony
- Wizualnie wyróżniać aktualnie ustawioną pozycję dla każdej kategorii
- Zachować spójność z istniejącym interfejsem pluginu

## Quality Gates

Te wymagania muszą być spełnione dla każdego user story:
- Ręczna weryfikacja w przeglądarce (wp-admin/edit.php?post_type=product&page=ecs64-category-order)
- Sprawdzenie czy AJAX zapisuje poprawnie wartości w bazie danych
- Sprawdzenie czy UI poprawnie odzwierciedla stan po odświeżeniu strony

## User Stories

### US-001: Dodanie przycisków pozycji przy kategoriach
As a user, I want to see L | R | - buttons next to each category in the tree view so that I can quickly identify and change mega menu column positions.

**Acceptance Criteria:**
- [ ] Każda kategoria w drzewie ma grupę trzech przycisków: L (left), R (right), - (not set)
- [ ] Przyciski są widoczne obok nazwy kategorii, nie zakłócają drag & drop
- [ ] Przyciski mają tooltips wyjaśniające ich funkcję (Left column, Right column, Not set)
- [ ] Przyciski są wystarczająco małe, by nie dominować interfejsu

### US-002: Wizualne wyróżnienie aktywnej pozycji
As a user, I want to see which position is currently active for each category so that I can quickly scan the current mega menu configuration.

**Acceptance Criteria:**
- [ ] Aktywny przycisk jest wyróżniony kolorem (np. niebieski/zielony background)
- [ ] Nieaktywne przyciski mają neutralny styl (szary/outline)
- [ ] Stan przycisków odzwierciedla rzeczywistą wartość z bazy danych przy ładowaniu strony
- [ ] Kategorie bez ustawionej wartości mają wyróżniony przycisk "-"

### US-003: Zapis pozycji przez AJAX
As a user, I want changes to be saved immediately when I click a position button so that I don't have to manually save after each change.

**Acceptance Criteria:**
- [ ] Kliknięcie przycisku wysyła żądanie AJAX do zapisania nowej wartości
- [ ] Wartość jest zapisywana jako term meta `merida_mega_menu_column_position`
- [ ] Dostępne wartości: "left", "right", "" (pusty string dla not set)
- [ ] Żądanie AJAX jest zabezpieczone nonce'em
- [ ] Tylko użytkownicy z odpowiednimi uprawnieniami mogą zmieniać wartości

### US-004: Feedback wizualny podczas zapisu
As a user, I want to see visual feedback when saving so that I know my change was successful.

**Acceptance Criteria:**
- [ ] Podczas zapisu przycisk pokazuje stan ładowania (spinner lub zmiana opacity)
- [ ] Po udanym zapisie przycisk zmienia stan na aktywny
- [ ] W przypadku błędu wyświetla się komunikat (np. toast notification)
- [ ] UI jest zablokowane dla danej kategorii podczas trwającego zapisu (prevent double-click)

### US-005: Integracja z istniejącym widokiem kategorii
As a user, I want the position controls to be seamlessly integrated into the existing category tree view so that the interface remains consistent and intuitive.

**Acceptance Criteria:**
- [ ] Przyciski są dodane do istniejącego szablonu kategorii w drzewie
- [ ] Stylowanie jest spójne z resztą interfejsu pluginu
- [ ] Przyciski nie wpływają na funkcjonalność drag & drop sortowania kategorii
- [ ] Widok działa poprawnie na różnych szerokościach ekranu (responsywność)

## Functional Requirements
- FR-1: System musi odczytywać wartość `merida_mega_menu_column_position` z term meta dla każdej kategorii
- FR-2: System musi zapisywać wartość `merida_mega_menu_column_position` jako term meta przez AJAX
- FR-3: Endpoint AJAX musi weryfikować nonce oraz uprawnienia użytkownika (manage_woocommerce lub edit_posts)
- FR-4: Przyciski muszą być renderowane dla wszystkich kategorii (głównych i podkategorii)
- FR-5: System musi obsługiwać trzy stany: "left", "right", "" (not set)
- FR-6: JavaScript musi aktualizować stan przycisków bez przeładowania strony

## Non-Goals
- Masowa zmiana pozycji dla wielu kategorii jednocześnie (bulk action)
- Filtrowanie kategorii po pozycji mega menu
- Tworzenie nowych pól meta - wykorzystujemy istniejące pole z motywu
- Modyfikacja samego mega menu - tylko zarządzanie wartością pola

## Technical Considerations
- Wykorzystać istniejący hook do renderowania elementów w drzewie kategorii
- Endpoint AJAX powinien być zarejestrowany w klasie odpowiedzialnej za AJAX w pluginie
- CSS powinien być dodany do istniejącego pliku stylów admina
- JavaScript może być osobnym plikiem lub dodany do istniejącego skryptu admina
- Nazwa pola meta: `merida_mega_menu_column_position` (zgodnie z motywem)

## Success Metrics
- Przyciski L | R | - są widoczne przy każdej kategorii w widoku drzewa
- Kliknięcie przycisku zapisuje wartość w bazie danych (weryfikacja przez wp_get_term_meta)
- Aktywny przycisk jest wizualnie wyróżniony
- Po odświeżeniu strony stan przycisków odpowiada wartościom w bazie
- Funkcjonalność drag & drop nadal działa poprawnie

## Open Questions
- Czy przyciski powinny być widoczne tylko dla użytkowników z określoną rolą, czy dla wszystkich z dostępem do tej strony?
- Czy w przyszłości planowane jest rozszerzenie o więcej opcji pozycjonowania?