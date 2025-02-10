import { useEffect } from "react";
import { Navigate, Outlet } from "react-router-dom";
import axiosClient from "../axiosClient";
import { useStateContext } from "../contexts/contextprovider";
import { useState } from "react";

import logo from '../assets/react.svg';
import sft from '../assets/searchfortruth.jpg'

// icons
import { MdMenuOpen } from "react-icons/md";
import { IoHomeOutline } from "react-icons/io5";
import { FaProductHunt } from "react-icons/fa";
import { FaUserCircle } from "react-icons/fa";
import { TbReportSearch } from "react-icons/tb";
import { IoLogoBuffer } from "react-icons/io";
import { CiSettings } from "react-icons/ci";
import { MdOutlineDashboard } from "react-icons/md";
import { IoLogOutOutline } from "react-icons/io5";
import { LogOutIcon } from "lucide-react";


const menuItems = [
  {
    icons: <IoHomeOutline size={30} color="white"/>,
    label: 'Home'
  },
  {
    icons: <FaProductHunt size={30} color="white" />,
    label: 'Products'
  },
  {
    icons: <MdOutlineDashboard size={30} color="white"/>,
    label: 'Dashboard'
  },
  {
    icons: <CiSettings size={30} color="white" />,
    label: 'Setting'
  },
  {
    icons: <IoLogoBuffer size={30} color="white"/>,
    label: 'Log'
  },
  {
    icons: <TbReportSearch size={30} color="white"/>,
    label: 'Report'
  },
  
]

export default function Sidebar() {

  const {user, token, setUser, setToken} = useStateContext();
  const [open, setOpen] = useState(true);
  const [activeIndex, setActiveIndex] = useState(false);

  const onLogout =  (ev) =>{
    ev.preventDefault();
    axiosClient.get('/logout')
    .then(({}) => {
       setUser(null)
       setToken(null)
    })
  }

  useEffect(() => {
    axiosClient.get('/user')
      .then(({data}) => {
         setUser(data)
      })
  }, [])
  
  return (
    <nav className={`shadow-md h-screen p-2 flex flex-col duration-500 bg-[#111b24] ${open ? 'w-60' : 'w-16'}`}>

      {/* Header */}
      <div className=' px-3 py-2 h-20 flex justify-between items-center'>
        <img src={logo} alt="Logo" className={`${open ? 'w-10' : 'w-0'} rounded-md`} />
        <div><MdMenuOpen color="white" size={34} className={` duration-500 cursor-pointer ${!open && ' rotate-180'}`} onClick={() => setOpen(!open)} /></div>
      </div>

      {/* Body */}

      <ul className='flex-1 h-screen'>
        {
          menuItems.map((item, index) => {
            return (
              <li key={index} onClick={() => setActiveIndex(index)} className={`px-3 py-2 my-2 rounded-md duration-300 cursor-pointer flex gap-2 items-center relative group
              ${activeIndex === index ? "bg-[#065601]" : "hover:bg-[#c8c8c833]"}`}>
                <div>{item.icons}</div>
                <p className={`${!open && 'w-0 translate-x-24'} text-white bg-opacity-0 duration-500 overflow-hidden`}>{item.label}</p>
                <p className={`${open && 'hidden'} absolute left-32 shadow-md rounded-md
                 w-0 p-0 text-black bg-opacity-0 duration-100 overflow-hidden group-hover:w-fit group-hover:p-2 group-hover:left-16
                `}>{item.label}</p>
              </li>
            )
          })
        }
      </ul>
      {/* footer */}
      <div className='flex items-center gap-2 px-3 py-2'>
        <div><FaUserCircle size={30} color="white"/></div>
        <div className={`leading-5 ${!open && 'w-0 translate-x-24'} duration-500 overflow-hidden`}>
          <p className="text-white bg-opacity-0 duration-500">{user.name}</p>

        </div>
        <div>
        <button className="px-4 py-2 rounded-lg  duration-300 cursor-pointer hover:bg-[#c8c8c833] transition" onClick={onLogout}>
          <LogOutIcon size={30} color="white"></LogOutIcon>
        </button>
        </div>
      </div>


    </nav>
  )
}