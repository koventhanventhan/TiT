import React, { createContext, useState, useEffect } from 'react';
import axios from 'axios';
const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';

export const SelectedChildContext = createContext();

export const SelectedChildProvider = ({ children }) => {
    const [selectedChild, setSelectedChild] = useState(() => {
        const savedChild = localStorage.getItem('selectedChild');
        return savedChild ? JSON.parse(savedChild) : null;
    });
    const [childrenList, setChildrenList] = useState([]);
    const [loadingChildren, setLoadingChildren] = useState(true);

    useEffect(() => {
        const fetchUserData = async () => {
            const token = localStorage.getItem('authToken') || localStorage.getItem('token') || sessionStorage.getItem('authToken');
            const userStr = localStorage.getItem('user') || sessionStorage.getItem('user');
            if (token && userStr) {
                try {
                    const user = JSON.parse(userStr);
                    if (user.role === 'user') {
                        // Use the new /user endpoint that returns children
                        const response = await axios.get(`${API_BASE_URL}/user`, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        const userData = response.data.user;
                        if (userData && userData.children) {
                            setChildrenList(userData.children);
                            
                            // Auto-select first child if none selected, or if selected is not in list
                            if (userData.children.length > 0) {
                                const currentId = selectedChild ? selectedChild.id : null;
                                const childExists = userData.children.find(c => c.id === currentId);
                                
                                if (!childExists) {
                                    setSelectedChild(userData.children[0]);
                                    localStorage.setItem('selectedChild', JSON.stringify(userData.children[0]));
                                }
                            } else {
                                setSelectedChild(null);
                                localStorage.removeItem('selectedChild');
                            }
                        }
                    }
                } catch (error) {
                    console.error("Error fetching user children:", error);
                }
            }
            setLoadingChildren(false);
        };
        fetchUserData();
    }, []);

    const changeSelectedChild = (child) => {
        setSelectedChild(child);
        localStorage.setItem('selectedChild', JSON.stringify(child));
    };

    return (
        <SelectedChildContext.Provider value={{ 
            selectedChild, 
            changeSelectedChild, 
            childrenList, 
            setChildrenList,
            loadingChildren
        }}>
            {children}
        </SelectedChildContext.Provider>
    );
};
