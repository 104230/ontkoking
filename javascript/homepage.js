const search = document.getElementById("search");
const buttons = document.querySelectorAll(".filters button");
const grid = document.querySelector(".grid");

let category = "alles";
let cards = [];

// Recepten ophalen uit de database
async function laadRecepten() {
    try {
        const response = await fetch("../php/recepten_ophalen.php");

        if (!response.ok) {
            throw new Error("Recepten konden niet worden opgehaald.");
        }

        const recepten = await response.json();

        // Oude recepten verwijderen
        grid.innerHTML = "";
        cards = [];

        // Controleren of er recepten zijn
        if (recepten.length === 0) {
            grid.innerHTML = "<p>Er zijn nog geen recepten toegevoegd.</p>";
            return;
        }

        // Receptkaarten maken
        recepten.forEach(function (recept) {
            const card = document.createElement("div");
            card.className = "card";

            card.dataset.category = recept.categorie.toLowerCase();

            // Foto tonen
            if (recept.afbeelding) {
                const photo = document.createElement("div");
                photo.className = "photo";

                const img = document.createElement("img");
                img.src = "../" + recept.afbeelding;
                img.alt = recept.naam;

                img.onerror = function () {
                    photo.style.display = "none";
                };

                photo.appendChild(img);
                card.appendChild(photo);
            }

            // Titel
            const title = document.createElement("h3");
            title.textContent = recept.naam;
            card.appendChild(title);

            // Bereidingstijd
            const time = document.createElement("p");
            time.textContent = "⏱️ " + recept.bereidingstijd + " min";
            card.appendChild(time);

            // Categorie
            const categoryText = document.createElement("p");
            categoryText.textContent = recept.categorie;
            card.appendChild(categoryText);

            // Maker van het recept
            if (recept.gebruiker_naam) {
                const maker = document.createElement("p");
                maker.textContent = "Door " + recept.gebruiker_naam;
                card.appendChild(maker);
            }

            // Hele receptkaart klikbaar maken
            card.style.cursor = "pointer";
            card.setAttribute("role", "link");
            card.setAttribute("tabindex", "0");
            card.setAttribute("aria-label", "Bekijk recept: " + recept.naam);

            function openRecept() {
                window.location.href =
                    "../php/recept_detail.php?id=" +
                    encodeURIComponent(recept.id);
            }

            card.addEventListener("click", openRecept);

            // Openen met Enter of spatie
            card.addEventListener("keydown", function (event) {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openRecept();
                }
            });

            // Kaart toevoegen aan de homepage
            grid.appendChild(card);
            cards.push(card);
        });

        // Filters toepassen
        showCards();

    } catch (error) {
        console.error("Fout bij het laden van recepten:", error);

        grid.innerHTML =
            "<p>De recepten konden niet worden geladen. Controleer de PHP-server en databaseverbinding.</p>";
    }
}

// Recepten zoeken en filteren
function showCards() {
    const text = search.value.toLowerCase().trim();

    cards.forEach(function (card) {
        const title = card.querySelector("h3").textContent.toLowerCase();

        const okCategory =
            category === "alles" ||
            card.dataset.category === category;

        const okText = title.includes(text);

        card.style.display =
            okCategory && okText ? "block" : "none";
    });
}

// Categorieknoppen
buttons.forEach(function (button) {
    button.addEventListener("click", function () {
        buttons.forEach(function (b) {
            b.classList.remove("dark");
        });

        button.classList.add("dark");

        category = button.dataset.category.toLowerCase();

        showCards();
    });
});

// Zoekbalk
search.addEventListener("input", showCards);

// Recepten laden zodra de homepage opent
laadRecepten();