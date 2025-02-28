import { createBrowserRouter } from "react-router-dom";
import Login from './views/Public/login.jsx';
import Register from './views/Public/register.jsx';
import DefaultLayout from "./Components/DefaultLayout.jsx";
import GuestLayout from "./Components/GuestLayout.jsx";

//admin views
import Venue from "./views/Admin/venue.jsx";
import Sport from "./views/Admin/sports.jsx";
import Varsity  from "./views/Admin/varsity.jsx";
import Document from "./views/Admin/documents.jsx"
import Dashboard from "./views/Admin/dashboard.jsx";
import Team from "./views/Admin/teams.jsx";




const router = createBrowserRouter ([
    {
        path: '/',
        element: <DefaultLayout/>,
        children: [
            {
                path: '/dashboard',
                element: <Dashboard/>,
            },
            {
                path: '/venue',
                element: <Venue/>,
            },
            {
                path: '/team',
                element: <Team/>,
            },
            {
                path: '/sport',
                element: <Sport/>,
            },
            {
                path: '/varsity',
                element: <Varsity/>,
            },
            {
                path: '/document',
                element: <Document/>,
            },
        ]
    },

    {
        path: '/',
        element: <GuestLayout/>,
        children: [
            {
                path: '/login',
                element: <Login/>,
            },      
            {
                path: '/register',
                element: <Register/>,
            }  
        ]
    },

    
    
]);

export default router;