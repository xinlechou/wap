/*
 * 名称：本地存储函数
 * 功能：兼容各大浏览器存储
 * 作者：轩枫
 * 日期：2015/06/11
 * 版本：V2.0
 */

/**
 * LocalStorage 本地存储兼容函数
 * getItem: 获取属性
 * setItem: 设置属性
 * removeItem: 删除属性
 *
 *
 * @example
 *
 iLocalStorage.setItem('key', 'value');
 console.log(iLocalStorage.getItem('key'));
 iLocalStorage.removeItem('key');
 *
 */

(function(window, document){

    // 1. IE7下的UserData对象
    var UserData = {
        userData: null,
        name: location.href,
        init: function(){
            // IE7下的初始化
            if(!UserData.userData){
                try{
                    UserData.userData = document.createElement("INPUT");
                    UserData.userData.type = "hidden";
                    UserData.userData.style.display = "none";
                    UserData.userData.addBehavior("#default#userData");
                    document.body.appendChild(UserData.userData);
                    var expires = new Date();
                    expires.setDate(expires.getDate() + 365);
                    UserData.userData.expires = expires.toUTCString();
                } catch(e){
                    return false;
                }
            }
            return true;
        },

        setItem: function(key, value){
            if(UserData.init()){
                UserData.userData.load(UserData.name);
                UserData.userData.setAttribute(key, value);
                UserData.userData.save(UserData.name);
            }
        },

        getItem: function(key){
            if(UserData.init()){
                UserData.userData.load(UserData.name);
                return UserData.userData.getAttribute(key);
            }
        },

        removeItem: function(key){
            if(UserData.init()){
                UserData.userData.load(UserData.name);
                UserData.userData.removeAttribute(key);
                UserData.userData.save(UserData.name);
            }
        }

    };

    // 2. 兼容只支持globalStorage的浏览器
    // 使用： var storage = getLocalStorage();
    function getLocalStorage(){
        if(typeof localStorage == "object"){
            return localStorage;
        } else if(typeof globalStorage == "object"){
            return globalStorage[location.href];
        } else if(typeof userData == "object"){
            return globalStorage[location.href];
        } else{
            throw new Error("不支持本地存储");
        }
    }
    var storage = getLocalStorage();

    function iLocalStorage(){

    }

    // 高级浏览器的LocalStorage对象
    iLocalStorage.prototype = {
        setItem: function(key, value){
            if(!window.localStorage){
                UserData.setItem(key, value);
            }else{
                storage.setItem(key, value);
            }
        },

        getItem: function(key){
            if(!window.localStorage){
                return UserData.getItem(key);
            }else{
                return storage.getItem(key);
            }
        },

        removeItem: function(key){
            if(!window.localStorage){
                UserData.removeItem(key);
            }else{
                storage.removeItem(key);
            }
        }
    }

    if (typeof module == 'object') {
        module.exports = new iLocalStorage();
    }else {
        window.iLocalStorage = new iLocalStorage();
    }

})(window, document);