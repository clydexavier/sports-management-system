import axios from "axios";
import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import axiosClient from "../axiosClient";
import { useStateContext } from "../contexts/contextprovider";
import { useForm } from 'react-hook-form';
import logo from '../assets/react.svg';

export default function register(){

    const nameRef = useRef();
    const emailRef = useRef();
    const passwordRef = useRef();
    const passwordConfirmationRef = useRef();

    const {setUser, setToken} = useStateContext();
    const [errorMessage, setErrorMessage] = useState("");

    //validates email
    const isValidEmail = (email) => {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    const Submit =  (ev) =>{
        ev.preventDefault();

        const name = nameRef.current.value;
        const email = emailRef.current.value;
        const password = passwordRef.current.value;
        const password_confirmation = passwordConfirmationRef.current.value;

        
        if(!name || !email || !password || !password_confirmation) {
            setErrorMessage("All fields are required.")
            return;
        }

        if (!isValidEmail(email)) {
            setErrorMessage("Please enter a valid email address.");
            return;
        }

       /* if (password !== confirmPassword) {
            setErrorMessage("Passwords do not match.");
            return;
        }*/

         // Clear error message if all validations pass
         setErrorMessage("");


        const payload = {
            name: nameRef.current.value,
            email: emailRef.current.value,
            password: passwordRef.current.value,
            password_confirmation: passwordConfirmationRef.current.value,
        }
        axiosClient.post("/register",payload).then(({data})=>{
            setUser(data.user);
            setToken(data.token);
    }).catch(err => {
        const response = err.response;
        if(response && response.status === 422){
            setErrorMessage("Registration failed. Please check your inputs.");
            console.log(response.data.errors);
        }
    });
}

    return(

        /*<div className="login-signup-container">
            <div className="login-header">
                <h1>
                    Sports Management System
                </h1>
                <div className="form">
                    <form onSubmit={Submit}>
                    {errorMessage && (
                            <p className="error-message" style={{ color: "red" }}>
                                {errorMessage}
                            </p>
                        )}
                        <input ref={nameRef} type="name" placeholder="Name" />
                        <input ref={emailRef} type="email" placeholder="Email"/>
                        <input ref={passwordRef} type="password" placeholder="Password" />
                        <input ref={confirmPasswordRef} type="password" placeholder="Confirm Password" />
                        <button className="login-signup-btn">Register</button>
                        <p className="login-signup-next">
                            Already Have An Account? <Link to= '/login'>Login</Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>*/
        <div className="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
        <div className="sm:mx-auto sm:w-full sm:max-w-sm">
          <img
            alt="Your Company"
            src={logo}
            className="mx-auto h-10 w-auto"
          />
          <h2 className="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">
            Sign in to your account
          </h2>
        </div>

        <div className="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
          <form className="space-y-6" onSubmit={Submit}>
               {errorMessage && (<div className="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                    <span className="font-medium">
                        {errorMessage}
                    </span> 
                </div>)}

                <div>
              <label htmlFor="text" className="block text-sm/6 font-medium text-gray-900">
                Name
              </label>
              <div className="mt-2">
                <input
                    ref={nameRef}
                    id="name"
                    name="email"
                    type="text"
                    required
                    autoComplete="email"
                    className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                />
              </div>
            </div>

            <div>
              <label htmlFor="email" className="block text-sm/6 font-medium text-gray-900">
                Email address
              </label>
              <div className="mt-2">
                <input
                    ref={emailRef}
                    id="email"
                    name="email"
                    type="email"
                    required
                    className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                />
              </div>
            </div>

            <div>
              <div className="flex items-center justify-between">
                <label htmlFor="password" className="block text-sm/6 font-medium text-gray-900">
                  Password
                </label>
              </div>
              <div className="mt-2">
                <input
                ref={passwordRef}
                  id="password"
                  name="password"
                  type="password"
                  required
                  className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                />
              </div>
            </div>

            <div>
              <div className="flex items-center justify-between">
                <label htmlFor="password" className="block text-sm/6 font-medium text-gray-900">
                  Confirm Password
                </label>
              </div>
              <div className="mt-2">
                <input
                ref={passwordConfirmationRef}
                  id="password_confirmation"
                  name="password_confirmation"
                  type="password"
                  required
                  className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                />
              </div>
            </div>


            <div>
              <button
                type="submit"
                className="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              >
                Register
              </button>
            </div>
          </form>

          <p className="mt-10 text-center text-sm/6 text-gray-500">
            Already Have an Account? <Link className="font-semibold text-indigo-600 hover:text-indigo-500" to= '/login'>Login</Link>
          </p>
        </div>
      </div>
    )   
}