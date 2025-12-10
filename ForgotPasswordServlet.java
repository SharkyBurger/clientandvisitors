import java.io.IOException;
import java.util.UUID;
import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

@WebServlet(name = "ForgotPasswordServlet", urlPatterns = {"/ForgotPasswordServlet"})
public class ForgotPasswordServlet extends HttpServlet {

    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        
        String userEmail = request.getParameter("email");

        // --- SIMULATION LOGIC ---
        // In a real app, you would check your Database here:
        // boolean exists = dao.checkEmail(userEmail);
        
        // For this test, we assume the email is always valid.
        
        // 1. Generate a random token (like a secure key)
        String token = UUID.randomUUID().toString();
        
        // 2. Create the "Magic Link"
        // This points to a 'reset_confirm.jsp' page (which you would create next)
        String magicLink = "reset_confirm.jsp?token=" + token + "&email=" + userEmail;

        // 3. Instead of emailing, we send this link back to the JSP page
        request.setAttribute("generatedLink", magicLink);
        
        // 4. Reload the page with the green box data
        request.getRequestDispatcher("forgot_password.jsp").forward(request, response);
    }
}