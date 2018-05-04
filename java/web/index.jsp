<%--
  Created by IntelliJ IDEA.
  User: martijn
  Date: 03/02/2017
  Time: 10:24
  To change this template use File | Settings | File Templates.
--%>
<%@ page contentType="text/html;charset=UTF-8" language="java" %>
<html>
  <head>
    <title>$Title$</title>
  </head>
  <body>

  <form action="/NLPServlet" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <textarea name="text"></textarea>
    <input type="submit" />
  </form>

  <hr/>

  <form action="/NLPServlet" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="file" name="file" />
    <input type="submit" />
  </form>

  </body>
</html>
