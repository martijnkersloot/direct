import javax.servlet.ServletContext;
import javax.servlet.ServletException;
import javax.servlet.annotation.MultipartConfig;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.Part;
import java.io.IOException;
import java.io.InputStream;
import java.io.PrintWriter;
import java.nio.charset.StandardCharsets;
import java.nio.file.Paths;

@WebServlet("/NLPServlet")
@MultipartConfig
public class NLPServlet extends HttpServlet {
    protected void doPost(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {

        ServletContext application = getServletContext();
        Parser parser = (Parser) application.getAttribute("parser");
        if(parser == null)
        {
            parser = new Parser();
            application.setAttribute("parser", parser);
        }
        else
        {
            System.out.println("- The parser already existed in the ServletContext - ");
        }

        if(request.getParameterMap().containsKey("text"))
        {
            String text = request.getParameter("text");
            byte[] bytes = text.getBytes(StandardCharsets.ISO_8859_1);
            text = new String(bytes, StandardCharsets.UTF_8);

            parser.setText(text);
            System.out.println(text);
        }
        else if(request.getPart("file") != null)
        {
            Part filePart = request.getPart("file");
            String fileName = Paths.get(filePart.getSubmittedFileName()).getFileName().toString();
            InputStream fileStream = filePart.getInputStream();
            String fileContent = convertStreamToString(fileStream);


            parser.setFile(fileName, fileContent);
        }
        else
        {
            System.out.println("Please upload file or post text");
            System.exit(0);
        }
        String xml = parser.getXML();

        response.setContentType("text/xml; charset=UTF-8");
        response.setCharacterEncoding("UTF-8");
        response.setHeader("Content-Disposition",
                "attachment;filename=download.xml");
        PrintWriter out = response.getWriter();
        out.println(xml);
        parser = null;
        out = null;
        System.gc();
    }

    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        // Set response content type
        response.setContentType("text/html");

        // Actual logic goes here.
        PrintWriter out = response.getWriter();
        out.println("Please upload the file by using a POST request.");
    }

    private String convertStreamToString(java.io.InputStream is) {
        java.util.Scanner s = new java.util.Scanner(is, "UTF-8").useDelimiter("\\A");
        return s.hasNext() ? s.next() : "";
    }
}