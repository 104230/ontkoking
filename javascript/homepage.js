const search = document.getElementById("search");
const buttons = document.querySelectorAll(".filters button");
const cards = document.querySelectorAll(".card");

let category = "alles";

function showCards() {
  const text = search.value.toLowerCase();

  cards.forEach(function (card) {
    const title = card.querySelector("h3").textContent.toLowerCase();
    const okCategory = category === "alles" || card.dataset.category === category;
    const okText = title.includes(text);

    card.style.display = okCategory && okText ? "block" : "none";
  });
}

buttons.forEach(function (button) {
  button.addEventListener("click", function () {
    buttons.forEach(function (b) {
      b.classList.remove("dark");
    });
    button.classList.add("dark");
    category = button.dataset.category;
    showCards();
  });
});

search.addEventListener("input", showCards);