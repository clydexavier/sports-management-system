import { createBrowserRouter } from "react-router-dom";
import Login from "./views/Public/login.jsx";
import Register from "./views/Public/register.jsx";
import GuestLayout from "./Components/GuestLayout.jsx";
import AdminLayout from "./Components/AdminLayout.jsx";
import GAMLayout from "./Components/GAMLayout.jsx";
import TSecretaryLayout from "./Components/TSecretaryLayout.jsx";
import SecretariatLayout from "./Components/SecretariatLayout.jsx";

// Admin Views
import Venue from "./views/Admin/venue.jsx";
import Sport from "./views/Admin/sports.jsx";
import Varsity from "./views/Admin/varsity.jsx";
import Document from "./views/Admin/documents.jsx";
import Dashboard from "./views/Admin/dashboard.jsx";
import Team from "./views/Admin/teams.jsx";

// Placeholder views for other roles (replace with actual components)
import GAMDashboard from "./views/GAM/dashboard.jsx";
import TSecretaryDashboard from "./views/TSecretary/dashboard.jsx";
import SecretariatDashboard from "./views/Secretariat/dashboard.jsx";

const router = createBrowserRouter([
    // Guest Routes (Public)
    {
        path: "/",
        element: <GuestLayout />,
        children: [
            { path: "/login", element: <Login /> },
            { path: "/register", element: <Register /> },
        ],
    },

    // Admin Routes
    {
        path: "/admin",
        element: <AdminLayout />,
        children: [
            { path: "/admin/dashboard", element: <Dashboard /> },
            { path: "/admin/venue", element: <Venue /> },
            { path: "/admin/team", element: <Team /> },
            { path: "/admin/sport", element: <Sport /> },
            { path: "/admin/varsity", element: <Varsity /> },
            { path: "/admin/document", element: <Document /> },
        ],
    },

    // GAM Routes
    {
        path: "/gam",
        element: <GAMLayout />,
        children: [{ path: "/gam/dashboard", element: <GAMDashboard /> }],
    },

    // Tournament Secretary Routes
    {
        path: "/tsecretary",
        element: <TSecretaryLayout />,
        children: [{ path: "/tsecretary/dashboard", element: <TSecretaryDashboard /> }],
    },

    // Secretariat Routes
    {
        path: "/secretariat",
        element: <SecretariatLayout />,
        children: [{ path: "/secretariat/dashboard", element: <SecretariatDashboard /> }],
    },
]);

export default router;
