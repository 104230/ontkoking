// php start: php -S 127.0.0.1:8000


const search = document.getElementById("search");
const buttons = document.querySelectorAll(".filters button");
const grid = document.querySelector(".grid");
console.log("Zoekbalk gevonden:", search);
let category = "alles";
let cards = [];

async function laadRecepten() {
    try {
        const response = await fetch("../php/recepten_ophalen.php");

        if (!response.ok) {
            throw new Error("Recepten konden niet worden opgehaald.");
        }

        const recepten = await response.json();

        grid.innerHTML = "";
        cards = [];

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

           
            const title = document.createElement("h3");
            title.textContent = recept.naam;
            card.appendChild(title);

            
            const time = document.createElement("p");
            time.textContent = "⏱️ " + recept.bereidingstijd + " min";
            card.appendChild(time);

            
            const categoryText = document.createElement("p");
            categoryText.textContent = recept.categorie;
            card.appendChild(categoryText);

            
            if (recept.gebruiker_naam) {
                const maker = document.createElement("p");
                maker.textContent = "Door " + recept.gebruiker_naam;
                card.appendChild(maker);
            }

            
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

            
            card.addEventListener("keydown", function (event) {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openRecept();
                }
            });

            grid.appendChild(card);
            cards.push(card);
        });

        
        showCards();

    } catch (error) {
        console.error("Fout bij het laden van recepten:", error);

        grid.innerHTML =
            "<p>De recepten konden niet worden geladen. Controleer de PHP-server en databaseverbinding.</p>";
    }
}


function showCards() {
    const text = search.value.toLowerCase().trim();

    cards.forEach(function (card) {
        const title = card.querySelector("h3").textContent.toLowerCase();
        const categoryName = card.dataset.category;

        
        const okText = title.includes(text);

       
        const okCategory =
            category === "alles" ||
            categoryName === category;

        
        if (okText && okCategory) {
            card.style.display = "";
        } else {
            card.style.display = "none";
        }
    });
}

search.addEventListener("input", function () {
    showCards();
});

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


laadRecepten();

