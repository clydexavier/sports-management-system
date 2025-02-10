import { createBrowserRouter } from "react-router-dom";
import Login from './views/login.jsx';
import Register from './views/register.jsx';
import DefaultLayout from "./Components/DefaultLayout.jsx";
import GuestLayout from "./Components/GuestLayout.jsx";
import Users from './views/users.jsx';
import Settings from './views/settings.jsx';



const router = createBrowserRouter ([

    {
        path: '/',
        element: <DefaultLayout/>,
        children: [
            {
                path: '/users',
                element: <Users/>,
            },
            {
                path: '/settings',
                element: <Settings/>,
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