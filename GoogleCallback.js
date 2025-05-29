import { useEffect, useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { authService } from "../lib/services/authoServices";

const GoogleCallback = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    const handleCallback = async () => {
      try {
        // Instead of extracting the code, we'll pass the full URL to the backend
        const fullCallbackUrl = window.location.href;
        
        // Check if we already have token in URL parameters (direct backend redirect)
        const params = new URLSearchParams(window.location.search);
        const token = params.get('token');
        
        if (token) {
          // For direct redirect with parameters in URL, handle like we would handle API response
          // Create mock response matching the structure from login endpoint
          const userId = params.get('user_id');
          const email = params.get('email');
          const name = params.get('name');
          const firstName = params.get('first_name');
          const lastName = params.get('last_name');
          const avatar = params.get('avatar');
          const status = params.get('status');
          const role = params.get('role');
          const todayReservationsCount = params.get('today_reservations_count');
          
          // Verify successful status
          if (status !== 'success') {
            throw new Error('Authentication failed with status: ' + status);
          }
          
          // Create user object matching structure expected in login code
          const userData = {
            id_compte: userId,
            email: email,
            name: name || `${firstName} ${lastName}`,
            nom: lastName || "",
            prenom: firstName || "",
            pfp: avatar || "",
            role: role || "",
            age: "",
            telephone: ""
          };
          
          // Create mock response
          const mockResponse = {
            status: true,
            code: 200,
            message: "Login successful!",
            data: {
              user_token: token,
              user: userData,
              roles: role ? [role] : [],
              token_type: 'Bearer',
              has_academie_membership: false,
              academie_memberships: [],
              has_teams: false,
              teams: [],
              today_reservations_count: parseInt(todayReservationsCount || '0', 10)
            }
          };
          
          // Process this mock response exactly like in SignIn.jsx
          processLoginResponse(mockResponse);
        } else {
          // Make API call to handle the Google callback
          const response = await authService.handleGoogleCallback(fullCallbackUrl);
          
          // Process the actual API response
          if (response.status) {
            processLoginResponse(response);
          } else {
            throw new Error(response.message || 'Google login failed');
          }
        }
      } catch (error) {
        console.error("Google authentication error:", error);
        setError(error.message || "An error occurred during Google authentication");
        setLoading(false);
      }
    };
    
    // Process login response exactly as in SignIn.jsx, with additional properties
    const processLoginResponse = (response) => {
      if (response.status && response.data) {
        // Store token
        sessionStorage.setItem("token", response.data.user_token);
        
        // Store user info
        const userData = response.data.user;
        sessionStorage.setItem("userId", userData.id_compte);
        sessionStorage.setItem("email", userData.email);
        
        // Store role/type
        if (userData.role) {
          sessionStorage.setItem("type", userData.role);
        }
        
        // Store name in multiple formats for different components
        const fullName = userData.name || `${userData.prenom} ${userData.nom}`;
        sessionStorage.setItem("name", fullName);
        sessionStorage.setItem("nom", userData.nom);
        sessionStorage.setItem("prenom", userData.prenom);
        
        // Store today's reservation count
        const todayReservationsCount = response.data.today_reservations_count || 0;
        sessionStorage.setItem("today_reservations_count", todayReservationsCount);
        console.log("Today's reservations count:", todayReservationsCount);
        
        // Additional user fields 
        if (userData.age) sessionStorage.setItem("age", userData.age);
        if (userData.telephone) sessionStorage.setItem("telephone", userData.telephone);
        
        // Store profile picture (prefer google_avatar if available)
        const profilePicture = userData.google_avatar || userData.pfp;
        sessionStorage.setItem("pfp", profilePicture);
        
        // Store academy membership status
        sessionStorage.setItem("has_academie_membership", response.data.has_academie_membership.toString());
        
        // Store academy memberships data if available
        if (response.data.academie_memberships && response.data.academie_memberships.length > 0) {
          // Store the full memberships array
          sessionStorage.setItem("academie_memberships", JSON.stringify(response.data.academie_memberships));
          
          // Store the first membership's ID for backward compatibility
          const firstMembership = response.data.academie_memberships[0];
          sessionStorage.setItem("academy_member_id", firstMembership.id_member);
        }
        
        // Store team information
        sessionStorage.setItem("has_teams", response.data.has_teams.toString());
        
        if (response.data.has_teams && response.data.teams && response.data.teams.length > 0) {
          // Store the full teams array
          sessionStorage.setItem("teams", JSON.stringify(response.data.teams));
          
          // Store the first team's ID for easy access
          const firstTeam = response.data.teams[0];
          sessionStorage.setItem("id_teams", firstTeam.id_teams);
          
          // If user is a captain of a team, store that info
          if (firstTeam.capitain && firstTeam.capitain.toString() === userData.id_compte.toString()) {
            sessionStorage.setItem("is_captain", "true");
          }
        }
        
        // Store roles
        if (response.data.roles && response.data.roles.length > 0) {
          sessionStorage.setItem("roles", JSON.stringify(response.data.roles));
        }
        
        // Set token type
        sessionStorage.setItem("token_type", response.data.token_type);
        
        // Store userdetails as a JSON string (complete user object)
        sessionStorage.setItem("userdetails", JSON.stringify(userData));

        console.log("Authentication successful - user data stored in session");
        console.log("User:", {
          id: userData.id_compte,
          name: fullName,
          role: userData.role,
          teams: response.data.has_teams ? response.data.teams.length : 0,
          academie: response.data.has_academie_membership ? response.data.academie_memberships.length : 0,
          today_reservations_count: response.data.today_reservations_count
        });

        // Navigate based on role
        if (userData.role === "admin") {
          navigate("/profile");
        } else {
          navigate("/profile");
        }
      } else {
        throw new Error(response.message || 'Authentication failed');
      }
    };

    handleCallback();
  }, [location, navigate]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-bright-green"></div>
          <p className="mt-4 text-xl font-semibold">Authenticating with Google...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100">
        <div className="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
          <div className="text-red-500 text-center mb-4 text-5xl">
            <span role="img" aria-label="Error">❌</span>
          </div>
          <h1 className="text-2xl font-bold text-center text-gray-800 mb-4">Authentication Failed</h1>
          <p className="text-gray-600 text-center mb-6">{error}</p>
          <button
            onClick={() => navigate("/sign-in")}
            className="w-full p-3 bg-bright-green text-black font-semibold rounded-lg hover:bg-green-500 transition"
          >
            Back to Login
          </button>
        </div>
      </div>
    );
  }

  return null;
};

export default GoogleCallback; 