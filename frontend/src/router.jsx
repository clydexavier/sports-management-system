import { createBrowserRouter } from "react-router-dom";
import Login from './views/login.jsx';
import Register from './views/register.jsx';
import DefaultLayout from "./Components/DefaultLayout.jsx";
import GuestLayout from "./Components/GuestLayout.jsx";
import Users from './views/users.jsx';
import Settings from './views/settings.jsx';
import Intramurals from "./views/intramurals.jsx";
import Dashboard from "./views/dashboard.jsx";
import Report from "./views/report.jsx";
import IntramuralForm from "./views/IntramuralForm.jsx";



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
                path: '/intramurals',
                element: <Intramurals/>,
                children: [ 
                    {
                        path: '/intramurals/add',
                        element: <IntramuralForm/> 
                    }
                ]
            },
            {
                path: '/users',
                element: <Users/>,
            },
            {
                path: '/settings',
                element: <Settings/>,
            },
            {
                path: '/report',
                element: <Report/>,
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