import { useEffect } from "react";
import { Navigate, Outlet } from "react-router-dom";
import axiosClient from "../axiosClient";
import { useStateContext } from "../contexts/contextprovider";
import Sidebar from "./Sidebar";
import Dashboard from "../views/Admin/dashboard";

export default function DefaultLayout(){
    const {user, token, setUser, setToken} = useStateContext();
    if(!token){
       return <Navigate to='/login'/>
    }

    return(
        <div id="defaultLayout">
         <div className="h-screen flex flex-row">
            <Sidebar className="h-screen"></Sidebar>
            <div className="h-screen w-screen flex flex-col">
                <Outlet/>
            </div>
            </div>
        </div>
    )
}