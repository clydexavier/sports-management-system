import { useStateContext } from "../contexts/contextprovider";
import { Navigate, Outlet } from "react-router-dom";

export default function GuestLayout(){
    const {token} = useStateContext();
    if(!token){
       return <Navigate to='/login'/>
    }

    return(
        <div className="h-screen">
            <Outlet />
        </div>
    )
}