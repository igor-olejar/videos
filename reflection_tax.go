package main

import (
	"fmt"
	"reflect"
	"time"
)

// User struct representing our Database Model
type User struct {
	ID        int
	Name      string
	Email     string
	Bio       string
	CreatedAt time.Time
}

func main() {
	const iterations = 10000

	// Simulating raw data from a database driver
	rawData := map[string]interface{}{
		"id":         1,
		"name":       "Gemini User",
		"email":      "user@example.com",
		"bio":        "This is a long bio string used for testing hydration overhead.",
		"created_at": time.Now(),
	}

	fmt.Printf("--- BENCHMARKING %d HYDRATIONS ---\n", iterations)

	// --- TEST 1: DIRECT ASSIGNMENT (The "Fast" Way) ---
	start := time.Now()
	for i := 0; i < iterations; i++ {
		_ = User{
			ID:        rawData["id"].(int),
			Name:      rawData["name"].(string),
			Email:     rawData["email"].(string),
			Bio:       rawData["bio"].(string),
			CreatedAt: rawData["created_at"].(time.Time),
		}
	}
	directTime := time.Since(start)
	fmt.Printf("DIRECT ASSIGNMENT: %v\n", directTime)

	// --- TEST 2: REFLECTION HYDRATION (The "ORM" Way) ---
	// This simulates what GORM does: looping through fields and using reflection to set values.
	start = time.Now()
	for i := 0; i < iterations; i++ {
		u := User{}
		val := reflect.ValueOf(&u).Elem()
		
		// This loop represents the overhead of an ORM mapping columns to fields
		for key, value := range rawData {
			// In a real ORM, there's even more logic here to match "created_at" to "CreatedAt"
			if key == "id" { val.Field(0).SetInt(int64(value.(int))) }
			if key == "name" { val.Field(1).SetString(value.(string)) }
			if key == "email" { val.Field(2).SetString(value.(string)) }
			if key == "bio" { val.Field(3).SetString(value.(string)) }
			if key == "created_at" { val.Field(4).Set(reflect.ValueOf(value)) }
		}
	}
	reflectTime := time.Since(start)
	fmt.Printf("REFLECTION (ORM):  %v\n", reflectTime)

	// --- RESULTS ---
	ratio := float64(reflectTime) / float64(directTime)
	fmt.Println("---------------------------------")
	fmt.Printf("The Go Reflection Tax is %.1fx slower.\n", ratio)
}