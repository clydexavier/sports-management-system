import { Navigate, Outlet } from "react-router-dom";
import Sidebar from "./Sidebar";
import { useStateContext } from "../contexts/contextprovider";

export default function AdminLayout() {
    const { user, token } = useStateContext();

    if (!token) {
        return <Navigate to="/login" />;
    }
    
    if (user?.role !== "admin") {
        return <Navigate to="/" />; // Redirect unauthorized users
    }

    return (
        <div id="adminLayout" className="h-screen flex">
            <Sidebar className="h-screen" />
            <div className="h-screen w-full flex flex-col">
                <Outlet />
            </div>
        </div>
    );
}
