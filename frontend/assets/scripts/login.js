
document.addEventListener("DOMContentLoaded", () => {
  const userField = document.getElementById("username");
  const pwField = document.getElementById("password");
  const toggleBtn = document.getElementById("togglePw");

  if (userField) userField.focus();

  if (toggleBtn && pwField) {
    toggleBtn.addEventListener("click", () => {
      const isPw = pwField.type === "password";
      pwField.type = isPw ? "text" : "password";
      toggleBtn.textContent = isPw ? "Verberg wachtwoord" : "Toon wachtwoord";
    });
  }
});