import { Navigate, Outlet } from "react-router-dom";
import Sidebar from "./Sidebar";
import { useStateContext } from "../contexts/contextprovider";

export default function SecretariatLayout() {
    const { user, token } = useStateContext();

    if (!token) {
        return <Navigate to="/login" />;
    }

    if (user?.role !== "secretariat") {
        return <Navigate to="/" />;
    }

    return (
        <div id="secretariatLayout" className="h-screen flex">
            <Sidebar className="h-screen" />
            <div className="h-screen w-full flex flex-col">
                <Outlet />
            </div>
        </div>
    );
}
