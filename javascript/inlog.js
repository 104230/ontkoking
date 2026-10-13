const form = document.getElementById("form");
const error = document.getElementById("error");

form.addEventListener("submit", function (event) {
  event.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  if (email === "" || password === "") {
    error.textContent = "Vul je e-mail en wachtwoord in.";
    return;
  }

  error.textContent = "";
  alert("Ingelogd als " + email);
});