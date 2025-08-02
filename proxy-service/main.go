package main

import (
	"encoding/json"
	"log"
	"math/rand"
	"net/http"
	"sync"
	"time"
)

type Proxy struct {
	URL string `json:"url"`
}

var (
	proxies = []string{
		"http://proxy1.example.com:8080",
		"http://proxy2.example.com:8080", 
		"http://proxy3.example.com:8080",
	}
	failed = make(map[string]time.Time)
	mu     sync.Mutex
)

func getProxy() Proxy {
	mu.Lock()
	defer mu.Unlock()
	
	// Clean old failures
	for url, t := range failed {
		if time.Since(t) > 5*time.Minute {
			delete(failed, url)
		}
	}
	
	// Get available proxies
	var available []string
	for _, p := range proxies {
		if _, isFailed := failed[p]; !isFailed {
			available = append(available, p)
		}
	}
	
	if len(available) == 0 {
		return Proxy{URL: "direct"}
	}
	
	return Proxy{URL: available[rand.Intn(len(available))]}
}

func main() {
	rand.Seed(time.Now().UnixNano())
	
	http.HandleFunc("/proxy", func(w http.ResponseWriter, r *http.Request) {
		json.NewEncoder(w).Encode(getProxy())
	})
	
	http.HandleFunc("/proxy/failed", func(w http.ResponseWriter, r *http.Request) {
		var req struct{ ProxyURL string `json:"proxy_url"` }
		if json.NewDecoder(r.Body).Decode(&req) == nil {
			mu.Lock()
			failed[req.ProxyURL] = time.Now()
			mu.Unlock()
		}
	})
	
	log.Println("Proxy service on :8080")
	log.Fatal(http.ListenAndServe(":8080", nil))
}