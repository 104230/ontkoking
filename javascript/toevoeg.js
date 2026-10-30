const file = document.getElementById("file");
const photo = document.getElementById("photo");
const plus = document.getElementById("plus");
const form = document.getElementById("form");
const title = document.getElementById("title");
const message = document.getElementById("message");

// Foto kiezen en preview laten zien
file.addEventListener("change", function () {
    const gekozenFoto = file.files[0];

    if (!gekozenFoto) {
        return;
    }

    // Controleer of het een afbeelding is
    if (!gekozenFoto.type.startsWith("image/")) {
        alert("Kies een geldige afbeelding.");
        file.value = "";
        return;
    }

    // Maximaal 5 MB
    if (gekozenFoto.size > 5 * 1024 * 1024) {
        alert("De afbeelding mag maximaal 5 MB zijn.");
        file.value = "";
        return;
    }

    // Oude preview verwijderen
    const oudeFoto = photo.querySelector("img");

    if (oudeFoto) {
        oudeFoto.remove();
    }

    // Plusje verwijderen
    if (plus) {
        plus.style.display = "none";
    }

    // Preview tonen
    const img = document.createElement("img");
    img.src = URL.createObjectURL(gekozenFoto);
    img.alt = "Voorbeeld van de gekozen receptfoto";

    img.style.width = "100%";
    img.style.height = "100%";
    img.style.objectFit = "cover";

    photo.appendChild(img);
});

// Formulier controleren voordat het wordt verstuurd
form.addEventListener("submit", function (event) {
    if (title.value.trim() === "") {
        event.preventDefault();
        alert("Vul een titel in.");
        title.focus();
        return;
    }

    // Het formulier wordt nu normaal naar PHP verstuurd.
    // PHP slaat het recept vervolgens op in de database.
});