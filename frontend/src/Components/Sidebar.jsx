import { useEffect, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import axiosClient from "../axiosClient";
import { useStateContext } from "../contexts/contextprovider";

import logo from '../assets/react.svg';

// icons
import { MdMenuOpen, MdOutlineDashboard } from "react-icons/md";
import { FaProductHunt, FaUserCircle } from "react-icons/fa";
import { TbReportSearch } from "react-icons/tb";
import { CiSettings } from "react-icons/ci";
import { LogOutIcon } from "lucide-react";

const menuItems = [
  {
    icon: <MdOutlineDashboard size={30} color="white" />,
    label: 'Dashboard',
    route: '/dashboard'
  },
  {
    label: 'Intramurals',
    route: '/intramurals'
  },
  {
    icon: <FaProductHunt size={30} color="white" />,
    label: 'Users',
    route: '/users'
  },
  {
    icon: <CiSettings size={30} color="white" />,
    label: 'Settings',
    route: '/settings'
  },
  {
    icon: <TbReportSearch size={30} color="white" />,
    label: 'Report',
    route: '/report'
  }
];

export default function Sidebar() {
  const { user, setUser, setToken } = useStateContext();
  const [open, setOpen] = useState(true);
  const location = useLocation(); // Get current route

  const onLogout = async (ev) => {
    ev.preventDefault();
    try {
      await axiosClient.get('/logout');
      setUser(null);
      setToken(null);
    } catch (error) {
      console.error("Logout failed:", error);
    }
  };

  useEffect(() => {
    axiosClient.get('/user')
      .then(({ data }) => setUser(data))
      .catch(err => console.error("Error fetching user:", err));
  }, []);

  return (
    <nav className={`shadow-md h-screen p-2 flex flex-col duration-500 bg-[#111b24] ${open ? 'w-60' : 'w-16'}`}>
      {/* Header */}
      <div className="px-3 py-2 h-20 flex justify-between items-center">
        <img src={logo} alt="Logo" className={`${open ? 'w-10' : 'w-0'} rounded-md`} />
        <MdMenuOpen color="white" size={34} className={`duration-500 cursor-pointer ${!open && 'rotate-180'}`} onClick={() => setOpen(!open)} />
      </div>

      {/* Menu Items */}
      <ul className="flex-1 h-screen">
        {menuItems.map((item, index) => (
          <li key={index}>
            <Link to={item.route}
              className={`px-3 py-2 my-2 rounded-md duration-300 cursor-pointer flex gap-2 items-center relative group 
              ${location.pathname === item.route ? "bg-[#065601]" : "hover:bg-[#c8c8c833]"}`}>
              <div>{item.icon}</div>
              <p className={`${!open && 'w-0 translate-x-24'} text-white bg-opacity-0 duration-500 overflow-hidden`}>{item.label}</p>
              <p className={`${open && 'hidden'} absolute left-32 shadow-md rounded-md
              w-0 p-0 text-black bg-opacity-0 duration-100 overflow-hidden group-hover:w-fit group-hover:p-2 group-hover:left-16`}>
                {item.label}
              </p>
            </Link>
          </li>
        ))}
      </ul>

      {/* Footer */}
      <div className="flex items-center gap-2 px-3 py-2">
        <FaUserCircle size={30} color="white" />
        <div className={`leading-5 ${!open && 'w-0 translate-x-24'} duration-500 overflow-hidden`}>
          <p className="text-white">{user?.name}</p>
        </div>
        <button className="px-4 py-2 rounded-lg duration-300 cursor-pointer hover:bg-[#c8c8c833]" onClick={onLogout}>
          <LogOutIcon size={30} color="white" />
        </button>
      </div>
    </nav>
  );
}
