const file = document.getElementById("file");
const photo = document.getElementById("photo");
const plus = document.getElementById("plus");
const form = document.getElementById("form");
const title = document.getElementById("title");


file.addEventListener("change", function () {
  const img = document.createElement("img");
  img.src = URL.createObjectURL(file.files[0]);
  plus.remove();
  photo.appendChild(img);
});


form.addEventListener("submit", function (event) {
  event.preventDefault();

  if (title.value === "") {
    alert("Vul een titel in.");
    return;
  }

  alert("Recept opgeslagen: " + title.value);
  window.location.href = "../pages/homepage.html";
});