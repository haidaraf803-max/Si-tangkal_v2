<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<title>SITANGKAL API</title>

<link rel="stylesheet"
href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">

<style>
body{
    margin:0;
}
</style>

</head>
<body>

<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>

<script>
window.onload = function() {

  SwaggerUIBundle({
      url: "./swagger.json",
      dom_id: "#swagger-ui",
      deepLinking: true,
      persistAuthorization: true
  });

};
</script>

</body>
</html>